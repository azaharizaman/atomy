<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Listeners;

use Nexus\FinanceOperations\Contracts\NotificationServiceInterface;
use Nexus\FinanceOperations\Contracts\TaskServiceInterface;
use Nexus\FinanceOperations\DTOs\GLReconciliationCompletedEvent;
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
        private ?NotificationServiceInterface $notificationService = null,
        private ?TaskServiceInterface $taskService = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the GL reconciliation completed event.
     * 
     * @param GLReconciliationCompletedEvent $event The reconciliation event containing:
     *                                              - tenantId: string
     *                                              - periodId: string
     *                                              - subledgerType: string
     *                                              - isReconciled: bool
     *                                              - discrepancies: array
     */
    public function handle(GLReconciliationCompletedEvent $event): void
    {
        $this->logger->info('Handling GL reconciliation completed event', [
            'tenant_id' => $event->tenantId,
            'subledger_type' => $event->subledgerType,
            'is_reconciled' => $event->isReconciled,
        ]);

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
                    // Invoke the task service
                    try {
                        $this->taskService->createAdjustmentTask($discrepancy);
                    } catch (\Throwable $e) {
                        $this->logger->error('Failed to create adjustment task', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Notify finance team
        if ($this->notificationService !== null) {
            $this->logger->info('Sending reconciliation notification');
            // Invoke the notification service
            try {
                $this->notificationService->notifyReconciliationCompleted([
                    'is_reconciled' => $event->isReconciled,
                    'discrepancy_count' => count($event->discrepancies),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send reconciliation notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('GL reconciliation event processed successfully');
    }
}
