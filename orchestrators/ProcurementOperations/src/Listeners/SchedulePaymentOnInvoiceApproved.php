<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Payable\Events\InvoiceApprovedEvent;
use Nexus\ProcurementOperations\Contracts\PaymentProcessingCoordinatorInterface;
use Nexus\ProcurementOperations\DataProviders\PaymentDataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener that schedules payments when vendor invoices are approved.
 *
 * Triggered by: InvoiceApprovedEvent from Nexus\Payable package
 *
 * Workflow:
 * 1. Receives invoice approved event
 * 2. Checks if auto-scheduling is enabled for vendor/tenant
 * 3. Calculates optimal payment date based on payment terms
 * 4. Schedules payment via PaymentProcessingCoordinator
 */
final readonly class SchedulePaymentOnInvoiceApproved
{
    public function __construct(
        private PaymentProcessingCoordinatorInterface $paymentCoordinator,
        private PaymentDataProvider $dataProvider,
        private ?LoggerInterface $logger = null,
    ) {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * Handle the invoice approved event.
     */
    public function __invoke(InvoiceApprovedEvent $event): void
    {
        $this->handle($event);
    }

    /**
     * Handle the invoice approved event.
     */
    public function handle(InvoiceApprovedEvent $event): void
    {
        $this->logger->info('Invoice approved, evaluating for payment scheduling', [
            'tenantId' => $event->tenantId,
            'invoiceId' => $event->invoiceId,
            'vendorId' => $event->vendorId,
        ]);

        // Check if auto-scheduling is enabled
        if (!$this->isAutoSchedulingEnabled($event->tenantId, $event->vendorId)) {
            $this->logger->debug('Auto-scheduling disabled for vendor', [
                'tenantId' => $event->tenantId,
                'vendorId' => $event->vendorId,
            ]);
            return;
        }

        // Calculate optimal payment date based on payment terms
        $paymentDate = $this->calculatePaymentDate($event);

        // Get default bank account for tenant
        $bankAccountId = $this->getDefaultBankAccount($event->tenantId);
        if ($bankAccountId === null) {
            $this->logger->warning('No default bank account configured, skipping auto-schedule', [
                'tenantId' => $event->tenantId,
            ]);
            return;
        }

        // Schedule the payment
        try {
            $result = $this->paymentCoordinator->schedule(
                tenantId: $event->tenantId,
                vendorBillIds: [$event->invoiceId],
                scheduledDate: $paymentDate,
                paymentMethod: $this->getDefaultPaymentMethod($event->tenantId),
                bankAccountId: $bankAccountId,
                scheduledBy: 'system',
            );

            $this->logger->info('Payment scheduled for approved invoice', [
                'tenantId' => $event->tenantId,
                'invoiceId' => $event->invoiceId,
                'scheduledDate' => $paymentDate->format('Y-m-d'),
                'paymentResult' => $result->status->value,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to schedule payment for approved invoice', [
                'tenantId' => $event->tenantId,
                'invoiceId' => $event->invoiceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if auto-scheduling is enabled for tenant/vendor.
     */
    private function isAutoSchedulingEnabled(string $tenantId, string $vendorId): bool
    {
        // In a real implementation, this would check tenant settings
        // and vendor-specific configuration
        return true;
    }

    /**
     * Calculate optimal payment date based on payment terms.
     */
    private function calculatePaymentDate(InvoiceApprovedEvent $event): \DateTimeImmutable
    {
        // Get payment terms from event or vendor settings
        $paymentTermsDays = $event->paymentTermsDays ?? 30;
        $invoiceDate = $event->invoiceDate ?? new \DateTimeImmutable();

        // Calculate due date
        $dueDate = $invoiceDate->modify("+{$paymentTermsDays} days");

        // Check for early payment discount opportunity
        if ($event->earlyPaymentDiscountPercent !== null && $event->earlyPaymentDiscountDays !== null) {
            $discountDate = $invoiceDate->modify("+{$event->earlyPaymentDiscountDays} days");
            $today = new \DateTimeImmutable();

            // If discount is still available, schedule for discount date
            if ($discountDate > $today) {
                $this->logger->debug('Scheduling for early payment discount', [
                    'discountPercent' => $event->earlyPaymentDiscountPercent,
                    'discountDate' => $discountDate->format('Y-m-d'),
                ]);
                return $discountDate;
            }
        }

        // Otherwise schedule for 2 business days before due date
        $paymentDate = $dueDate->modify('-2 days');

        // Ensure payment date is not in the past
        $today = new \DateTimeImmutable();
        if ($paymentDate < $today) {
            $paymentDate = $today->modify('+1 day');
        }

        return $paymentDate;
    }

    /**
     * Get default bank account for tenant.
     */
    private function getDefaultBankAccount(string $tenantId): ?string
    {
        // In a real implementation, query tenant settings
        return 'default-bank-account';
    }

    /**
     * Get default payment method for tenant.
     */
    private function getDefaultPaymentMethod(string $tenantId): string
    {
        // In a real implementation, query tenant settings
        return 'bank_transfer';
    }
}
