<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Listeners;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener for budget threshold exceeded events.
 * 
 * Handles budget alert activities such as:
 * - Sending alerts to budget managers
 * - Creating approval workflows
 * - Locking budgets if necessary
 * 
 * @since 1.0.0
 */
final readonly class OnBudgetThresholdExceeded
{
    public function __construct(
        private ?object $notificationService = null,
        private ?object $workflowService = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the budget threshold exceeded event.
     * 
     * @param object $event The threshold event containing:
     *                      - tenantId: string
     *                      - budgetId: string
     *                      - threshold: float
     *                      - actualPercent: float
     *                      - costCenterId: string|null
     */
    public function handle(object $event): void
    {
        $this->logger->warning('Handling budget threshold exceeded event', [
            'tenant_id' => $event->tenantId ?? 'unknown',
            'budget_id' => $event->budgetId ?? 'unknown',
            'threshold' => $event->threshold ?? 0,
            'actual_percent' => $event->actualPercent ?? 0,
        ]);

        try {
            // Determine severity based on threshold
            $severity = $this->determineSeverity($event->threshold ?? 0, $event->actualPercent ?? 0);

            // Send notifications
            if ($this->notificationService !== null) {
                $this->logger->info('Sending budget threshold alert', [
                    'severity' => $severity,
                    'budget_id' => $event->budgetId,
                ]);
                // Invoke the notification service
                try {
                    $this->notificationService->sendAlert([
                        'type' => 'budget_threshold',
                        'budget_id' => $event->budgetId,
                        'severity' => $severity,
                        'threshold' => $event->threshold ?? 0,
                        'actual' => $event->actualPercent ?? 0,
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to send budget threshold alert', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Create approval workflow for critical thresholds
            if ($severity === 'critical' && $this->workflowService !== null) {
                $this->logger->info('Creating budget approval workflow', [
                    'budget_id' => $event->budgetId,
                ]);
                // Invoke the workflow service
                try {
                    $this->workflowService->createApprovalWorkflow([
                        'type' => 'budget_threshold',
                        'budget_id' => $event->budgetId,
                        'severity' => $severity,
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to create budget approval workflow', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('Budget threshold event processed', [
                'severity' => $severity,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Exception in budget threshold event handler', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine severity level based on threshold and actual percentage.
     */
    private function determineSeverity(float $threshold, float $actualPercent): string
    {
        if ($actualPercent >= 100) {
            return 'critical';
        }
        if ($actualPercent >= 90) {
            return 'warning';
        }
        return 'info';
    }
}
