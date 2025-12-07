<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Payable\Events\InvoiceMatchFailedEvent;
use Nexus\Workflow\Contracts\TaskManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Notifies approvers when invoice matching detects variance beyond tolerance.
 *
 * This listener handles:
 * - Determining appropriate approvers based on variance amount
 * - Creating approval tasks in workflow system
 * - Sending notifications via configured channels
 *
 * Approval Routing Logic:
 * - Minor variance (< 5%): AP Clerk
 * - Moderate variance (5-10%): AP Supervisor
 * - Major variance (> 10%): Finance Manager
 *
 * Notification Channels:
 * - Email: Always sent
 * - In-App: Always sent
 * - SMS: Only for major variance
 */
final readonly class NotifyApproversOnVarianceDetected
{
    /**
     * Variance thresholds for approval routing.
     */
    private const MINOR_VARIANCE_THRESHOLD = 5.0;    // Up to 5%
    private const MODERATE_VARIANCE_THRESHOLD = 10.0; // 5-10%
    // Above 10% is considered major variance

    public function __construct(
        private NotificationManagerInterface $notifier,
        private ?TaskManagerInterface $taskManager = null,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Handle the invoice match failed event.
     */
    public function handle(InvoiceMatchFailedEvent $event): void
    {
        $this->getLogger()->info('Processing variance notification for failed match', [
            'vendor_bill_id' => $event->vendorBillId,
            'vendor_bill_number' => $event->vendorBillNumber,
            'purchase_order_id' => $event->purchaseOrderId,
            'price_variance_percent' => $event->priceVariancePercent,
            'quantity_variance_percent' => $event->quantityVariancePercent,
            'failure_reason' => $event->failureReason,
        ]);

        try {
            // Determine variance severity and approval level
            $maxVariance = max(
                abs($event->priceVariancePercent),
                abs($event->quantityVariancePercent),
            );
            $approvalLevel = $this->determineApprovalLevel($maxVariance);

            $this->getLogger()->info('Determined approval level for variance', [
                'vendor_bill_id' => $event->vendorBillId,
                'max_variance_percent' => $maxVariance,
                'approval_level' => $approvalLevel,
            ]);

            // Create approval task if task manager is available
            if ($this->taskManager !== null) {
                $this->createApprovalTask($event, $approvalLevel, $maxVariance);
            }

            // Send notifications to appropriate approvers
            $this->sendNotifications($event, $approvalLevel, $maxVariance);

            $this->getLogger()->info('Successfully processed variance notifications', [
                'vendor_bill_id' => $event->vendorBillId,
                'approval_level' => $approvalLevel,
            ]);

        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to process variance notification', [
                'vendor_bill_id' => $event->vendorBillId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            // Don't rethrow - notification failure shouldn't block processing
            // The invoice remains in pending_match status for manual review
        }
    }

    /**
     * Determine the approval level based on variance percentage.
     */
    private function determineApprovalLevel(float $variancePercent): string
    {
        return match (true) {
            $variancePercent <= self::MINOR_VARIANCE_THRESHOLD => 'ap_clerk',
            $variancePercent <= self::MODERATE_VARIANCE_THRESHOLD => 'ap_supervisor',
            default => 'finance_manager',
        };
    }

    /**
     * Create an approval task in the workflow system.
     */
    private function createApprovalTask(
        InvoiceMatchFailedEvent $event,
        string $approvalLevel,
        float $maxVariance,
    ): void {
        $this->getLogger()->debug('Creating variance approval task', [
            'vendor_bill_id' => $event->vendorBillId,
            'approval_level' => $approvalLevel,
        ]);

        $taskDescription = sprintf(
            'Invoice %s requires variance approval. PO: %s, Price Variance: %.2f%%, Qty Variance: %.2f%%. Reason: %s',
            $event->vendorBillNumber,
            $event->purchaseOrderNumber,
            $event->priceVariancePercent,
            $event->quantityVariancePercent,
            $event->failureReason,
        );

        $this->taskManager?->create(
            tenantId: $event->tenantId,
            taskType: 'invoice_variance_approval',
            title: "Approve Invoice Variance: {$event->vendorBillNumber}",
            description: $taskDescription,
            assignToRole: $approvalLevel,
            entityType: 'vendor_bill',
            entityId: $event->vendorBillId,
            priority: $this->determinePriority($maxVariance),
            dueDate: $this->calculateDueDate($approvalLevel),
            metadata: [
                'vendor_bill_id' => $event->vendorBillId,
                'vendor_bill_number' => $event->vendorBillNumber,
                'purchase_order_id' => $event->purchaseOrderId,
                'purchase_order_number' => $event->purchaseOrderNumber,
                'vendor_id' => $event->vendorId,
                'price_variance_percent' => $event->priceVariancePercent,
                'quantity_variance_percent' => $event->quantityVariancePercent,
                'price_tolerance_percent' => $event->priceTolerancePercent,
                'quantity_tolerance_percent' => $event->quantityTolerancePercent,
                'invoice_amount_cents' => $event->invoiceAmountCents,
                'po_amount_cents' => $event->poAmountCents,
                'received_amount_cents' => $event->receivedAmountCents,
                'currency' => $event->currency,
                'variance_details' => $event->varianceDetails,
                'failure_reason' => $event->failureReason,
            ],
        );

        $this->getLogger()->info('Created variance approval task', [
            'vendor_bill_id' => $event->vendorBillId,
            'task_type' => 'invoice_variance_approval',
            'assigned_to_role' => $approvalLevel,
        ]);
    }

    /**
     * Send notifications to appropriate approvers.
     */
    private function sendNotifications(
        InvoiceMatchFailedEvent $event,
        string $approvalLevel,
        float $maxVariance,
    ): void {
        // Build notification data
        $notificationData = [
            'vendor_bill_number' => $event->vendorBillNumber,
            'purchase_order_number' => $event->purchaseOrderNumber,
            'price_variance_percent' => sprintf('%.2f', $event->priceVariancePercent),
            'quantity_variance_percent' => sprintf('%.2f', $event->quantityVariancePercent),
            'invoice_amount' => $this->formatCurrency($event->invoiceAmountCents, $event->currency),
            'po_amount' => $this->formatCurrency($event->poAmountCents, $event->currency),
            'received_amount' => $this->formatCurrency($event->receivedAmountCents, $event->currency),
            'failure_reason' => $event->failureReason,
            'approval_level' => $this->getApprovalLevelLabel($approvalLevel),
        ];

        // Determine notification channels based on severity
        $channels = $this->getNotificationChannels($approvalLevel, $maxVariance);

        // Send notifications
        foreach ($channels as $channel) {
            $this->sendNotificationToChannel($event, $channel, $approvalLevel, $notificationData);
        }
    }

    /**
     * Get notification channels based on approval level and variance.
     *
     * @return array<string>
     */
    private function getNotificationChannels(string $approvalLevel, float $maxVariance): array
    {
        $channels = ['email', 'in_app'];

        // Add SMS for major variance (finance manager level)
        if ($approvalLevel === 'finance_manager' || $maxVariance > self::MODERATE_VARIANCE_THRESHOLD) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    /**
     * Send notification to a specific channel.
     *
     * @param array<string, mixed> $data
     */
    private function sendNotificationToChannel(
        InvoiceMatchFailedEvent $event,
        string $channel,
        string $approvalLevel,
        array $data,
    ): void {
        $this->getLogger()->debug('Sending variance notification', [
            'vendor_bill_id' => $event->vendorBillId,
            'channel' => $channel,
            'approval_level' => $approvalLevel,
        ]);

        try {
            $this->notifier->sendToRole(
                tenantId: $event->tenantId,
                role: $approvalLevel,
                channel: $channel,
                template: 'procurement.invoice_variance_detected',
                data: $data,
                metadata: [
                    'entity_type' => 'vendor_bill',
                    'entity_id' => $event->vendorBillId,
                    'event_type' => 'invoice_match_failed',
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->warning('Failed to send notification via channel', [
                'vendor_bill_id' => $event->vendorBillId,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            // Continue with other channels
        }
    }

    /**
     * Determine task priority based on variance.
     */
    private function determinePriority(float $variancePercent): string
    {
        return match (true) {
            $variancePercent <= self::MINOR_VARIANCE_THRESHOLD => 'low',
            $variancePercent <= self::MODERATE_VARIANCE_THRESHOLD => 'medium',
            default => 'high',
        };
    }

    /**
     * Calculate due date based on approval level.
     */
    private function calculateDueDate(string $approvalLevel): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable();

        $days = match ($approvalLevel) {
            'ap_clerk' => 3,
            'ap_supervisor' => 2,
            'finance_manager' => 1,
            default => 3,
        };
        return $this->addBusinessDays($now, $days);
    }

    /**
     * Add business days (Mon-Fri) to a date.
     */
    private function addBusinessDays(\DateTimeImmutable $date, int $days): \DateTimeImmutable
    {
        if ($days === 0) {
            return $date;
        }
        
        if ($days < 0) {
            throw new \InvalidArgumentException('Days must be non-negative');
        }
        
        $result = $date;
        $added = 0;
        while ($added < $days) {
            $result = $result->modify('+1 day');
            $dayOfWeek = (int) $result->format('N'); // 1 (Mon) to 7 (Sun)
            if ($dayOfWeek < 6) {
                $added++;
            }
        }
        return $result;
    }

    /**
     * Format currency amount for display.
     */
    private function formatCurrency(int $amountCents, string $currency): string
    {
        return sprintf('%s %.2f', $currency, $amountCents / 100);
    }

    /**
     * Get human-readable label for approval level.
     */
    private function getApprovalLevelLabel(string $approvalLevel): string
    {
        return match ($approvalLevel) {
            'ap_clerk' => 'AP Clerk',
            'ap_supervisor' => 'AP Supervisor',
            'finance_manager' => 'Finance Manager',
            default => 'Approver',
        };
    }
}
