<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Listeners;

use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener that sends notifications when significant variances are detected
 */
final readonly class NotifyOnSignificantVariance
{
    public function __construct(
        private NotificationManagerInterface $notifier,
        private ?AuditLogManagerInterface $auditLogger = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Handle the significant variance detected event
     *
     * @param object $event The variance detected event (framework-specific)
     * @return void
     */
    public function handle(object $event): void
    {
        $tenantId = $event->tenantId ?? '';
        $periodId = $event->periodId ?? '';
        $varianceType = $event->varianceType ?? 'unknown';
        $varianceAmount = $event->varianceAmount ?? 0;
        $variancePercentage = $event->variancePercentage ?? 0;
        $accountId = $event->accountId ?? '';
        $accountName = $event->accountName ?? 'Unknown Account';
        $threshold = $event->threshold ?? 10.0;
        $recipients = $event->notifyRecipients ?? [];

        $this->logger->info('Significant variance detected, sending notifications', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'variance_type' => $varianceType,
            'variance_percentage' => $variancePercentage,
            'threshold' => $threshold,
        ]);

        try {
            // Build notification message
            $message = sprintf(
                'Significant %s variance detected for %s: %.2f%% (threshold: %.2f%%)',
                $varianceType,
                $accountName,
                $variancePercentage,
                $threshold
            );

            // Send notifications to all recipients
            foreach ($recipients as $recipientId) {
                $this->notifier->send(
                    recipient: $recipientId,
                    channel: 'in_app',
                    template: 'variance.significant_detected',
                    data: [
                        'message' => $message,
                        'tenant_id' => $tenantId,
                        'period_id' => $periodId,
                        'account_id' => $accountId,
                        'account_name' => $accountName,
                        'variance_type' => $varianceType,
                        'variance_amount' => $varianceAmount,
                        'variance_percentage' => $variancePercentage,
                        'threshold' => $threshold,
                        'severity' => $this->determineSeverity($variancePercentage, $threshold),
                    ]
                );
            }

            // Also send email for critical variances
            if ($variancePercentage > ($threshold * 2)) {
                foreach ($recipients as $recipientId) {
                    $this->notifier->send(
                        recipient: $recipientId,
                        channel: 'email',
                        template: 'variance.critical_variance_alert',
                        data: [
                            'subject' => sprintf('Critical Variance Alert: %s', $accountName),
                            'message' => $message,
                            'account_name' => $accountName,
                            'variance_percentage' => $variancePercentage,
                            'variance_amount' => $varianceAmount,
                        ]
                    );
                }
            }

            $this->logger->info('Variance notifications sent', [
                'recipients_count' => count($recipients),
                'channels' => $variancePercentage > ($threshold * 2) ? ['in_app', 'email'] : ['in_app'],
            ]);

            // Log to audit trail
            $this->auditLogger?->log(
                entityId: $accountId,
                action: 'significant_variance_notified',
                description: $message,
                metadata: [
                    'tenant_id' => $tenantId,
                    'period_id' => $periodId,
                    'variance_type' => $varianceType,
                    'variance_percentage' => $variancePercentage,
                    'recipients_count' => count($recipients),
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send variance notifications', [
                'tenant_id' => $tenantId,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            // Don't throw - notification failure shouldn't break the main flow
        }
    }

    /**
     * Determine severity level based on variance percentage
     */
    private function determineSeverity(float $variancePercentage, float $threshold): string
    {
        $ratio = abs($variancePercentage) / $threshold;

        return match (true) {
            $ratio >= 3.0 => 'critical',
            $ratio >= 2.0 => 'high',
            $ratio >= 1.5 => 'medium',
            default => 'low',
        };
    }
}
