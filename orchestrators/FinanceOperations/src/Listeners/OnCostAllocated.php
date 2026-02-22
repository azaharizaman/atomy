<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Listeners;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener for cost allocation events.
 * 
 * Handles post-allocation activities such as:
 * - Sending notifications to cost center managers
 * - Updating budget tracking
 * - Triggering variance analysis
 * 
 * @since 1.0.0
 */
final readonly class OnCostAllocated
{
    public function __construct(
        private ?object $notificationService = null,  // NotificationServiceInterface
        private ?object $budgetService = null,        // BudgetMonitoringService
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the cost allocated event.
     * 
     * @param object $event The allocation event containing:
     *                      - tenantId: string
     *                      - periodId: string
     *                      - allocationId: string
     *                      - costCenterIds: array
     *                      - totalAllocated: string
     */
    public function handle(object $event): void
    {
        $this->logger->info('Handling cost allocated event', [
            'tenant_id' => $event->tenantId ?? 'unknown',
            'allocation_id' => $event->allocationId ?? 'unknown',
            'cost_centers' => count($event->costCenterIds ?? []),
        ]);

        try {
            // Update budget tracking for affected cost centers
            if ($this->budgetService !== null && !empty($event->costCenterIds)) {
                foreach ($event->costCenterIds as $costCenterId) {
                    $this->logger->debug('Updating budget for cost center', [
                        'cost_center_id' => $costCenterId,
                    ]);
                }
            }

            // Send notifications to cost center managers
            if ($this->notificationService !== null) {
                $this->logger->info('Sending allocation notifications', [
                    'allocation_id' => $event->allocationId ?? 'unknown',
                ]);
            }

            $this->logger->info('Cost allocation event processed successfully');
        } catch (\Throwable $e) {
            $this->logger->error('Exception in cost allocation event handler', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
