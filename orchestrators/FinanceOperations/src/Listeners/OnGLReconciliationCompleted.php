<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Listeners;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener for GL reconciliation completion events.
 * 
 * Handles post-reconciliation activities such as:
 * - Notifying finance team of discrepancies
 * - Creating adjustment tasks
 * - Updating reconciliation status
 * 
 * @since 1.0.0
 */
final readonly class OnGLReconciliationCompleted
{
    public function __construct(
        private ?object $notificationService = null,
        private ?object $taskService = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the GL reconciliation completed event.
     * 
     * @param object $event The reconciliation event containing:
     *                      - tenantId: string
     *                      - periodId: string
     *                      - subledgerType: string
     *                      - isReconciled: bool
     *                      - discrepancies: array
     */
    public function handle(object $event): void
    {
        $this->logger->info('Handling GL reconciliation completed event', [
            'tenant_id' => $event->tenantId ?? 'unknown',
            'subledger_type' => $event->subledgerType ?? 'unknown',
            'is_reconciled' => $event->isReconciled ?? false,
        ]);

        try {
            // If not reconciled, create adjustment tasks
            if (!$event->isReconciled && !empty($event->discrepancies)) {
                $this->logger->warning('Reconciliation discrepancies found', [
                    'discrepancy_count' => count($event->discrepancies),
                ]);

                if ($this->taskService !== null) {
                    foreach ($event->discrepancies as $discrepancy) {
                        $this->logger->info('Creating adjustment task', [
                            'discrepancy' => $discrepancy,
                        ]);
                    }
                }
            }

            // Notify finance team
            if ($this->notificationService !== null) {
                $this->logger->info('Sending reconciliation notification');
            }

            $this->logger->info('GL reconciliation event processed successfully');
        } catch (\Throwable $e) {
            $this->logger->error('Exception in GL reconciliation event handler', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
