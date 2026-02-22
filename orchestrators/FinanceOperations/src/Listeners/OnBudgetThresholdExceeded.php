<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Listeners;

use Nexus\FinanceOperations\Contracts\NotificationServiceInterface;
use Nexus\FinanceOperations\Contracts\WorkflowServiceInterface;
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
        private ?NotificationServiceInterface $notificationService = null,
        private ?WorkflowServiceInterface $workflowService = null,
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
        $budgetId = $event->budgetId ?? 'unknown';
        $tenantId = $event->tenantId ?? 'unknown';
        $threshold = $event->threshold ?? 0;
        $actualPercent = $event->actualPercent ?? 0;

        $this->logger->warning('Handling budget threshold exceeded event', [
            'tenant_id' => $tenantId,
            'budget_id' => $budgetId,
            'threshold' => $threshold,
            'actual_percent' => $actualPercent,
        ]);

        try {
            // Determine severity based on threshold
            $severity = $this->determineSeverity($threshold, $actualPercent);

            // Send notifications
            if ($this->notificationService !== null) {
                $this->logger->info('Sending budget threshold alert', [
                    'severity' => $severity,
                    'budget_id' => $budgetId,
                ]);
                // Invoke the notification service
                try {
                    $this->notificationService->sendAlert([
                        'type' => 'budget_threshold',
                        'budget_id' => $budgetId,
                        'severity' => $severity,
                        'threshold' => $threshold,
                        'actual' => $actualPercent,
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
                    'budget_id' => $budgetId,
                ]);
                // Invoke the workflow service
                try {
                    $this->workflowService->createApprovalWorkflow([
                        'type' => 'budget_threshold',
                        'budget_id' => $budgetId,
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
     *
     * @param float $threshold The threshold that was exceeded
     * @param float $actualPercent The actual utilization percentage
     * @return string The severity level: 'critical', 'warning', or 'info'
     */
    private function determineSeverity(float $threshold, float $actualPercent): string
    {
        // Critical if threshold is 100% or actual is 100%+
        if ($actualPercent >= 100 || $threshold >= 100) {
            return 'critical';
        }
        // Warning if threshold is 90%+ or actual is 90%+
        if ($actualPercent >= 90 || $threshold >= 90) {
            return 'warning';
        }
        return 'info';
    }
}
