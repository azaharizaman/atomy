<?php

declare(strict_types=1);

namespace Nexus\Budget\Services;

use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetAnalyticsRepositoryInterface;
use Nexus\Budget\Enums\AlertSeverity;
use Nexus\Budget\Events\BudgetUtilizationAlertEvent;
use Nexus\Budget\ValueObjects\UtilizationAlert;
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Utilization Alert Manager
 * 
 * Monitors budget utilization and triggers alerts when thresholds are exceeded.
 * Integrates with Notifier package for multi-channel notifications.
 */
final readonly class UtilizationAlertManager
{
    public function __construct(
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetAnalyticsRepositoryInterface $analyticsRepository,
        private NotificationManagerInterface $notificationManager,
        private SettingsManagerInterface $settings,
        private AuditLoggerInterface $auditLogger,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    /**
     * Check budget utilization and trigger alerts if needed
     */
    public function checkUtilization(string $budgetId): ?UtilizationAlert
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            return null;
        }

        $utilizationPct = $this->calculateUtilization($budget);
        $thresholds = $this->getAlertThresholds();

        foreach ($thresholds as $threshold => $severity) {
            if ($utilizationPct >= $threshold) {
                return $this->createAlert($budget, $utilizationPct, $severity);
            }
        }

        return null;
    }

    /**
     * Calculate current utilization percentage
     */
    private function calculateUtilization(object $budget): float
    {
        $allocated = $budget->getAllocatedAmount()->getAmount();
        if ($allocated == 0) {
            return 0.0;
        }

        // Include both actual spent and commitments for early warning
        $actual = $budget->getActualAmount()->getAmount();
        $committed = $budget->getCommittedAmount()->getAmount();
        
        return (($actual + $committed) / $allocated) * 100;
    }

    /**
     * Get alert thresholds from settings (descending order)
     */
    private function getAlertThresholds(): array
    {
        return [
            $this->settings->getFloat('budget.alert_critical_threshold', 95.0) => AlertSeverity::Critical,
            $this->settings->getFloat('budget.alert_high_threshold', 85.0) => AlertSeverity::High,
            $this->settings->getFloat('budget.alert_medium_threshold', 75.0) => AlertSeverity::Medium,
            $this->settings->getFloat('budget.alert_low_threshold', 60.0) => AlertSeverity::Low,
        ];
    }

    /**
     * Create utilization alert
     */
    private function createAlert(
        object $budget,
        float $utilizationPct,
        AlertSeverity $severity
    ): UtilizationAlert {
        $alert = new UtilizationAlert(
            budgetId: $budget->getId(),
            budgetName: $budget->getName(),
            periodId: $budget->getPeriodId(),
            utilizationPercentage: $utilizationPct,
            allocatedAmount: $budget->getAllocatedAmount(),
            actualAmount: $budget->getActualAmount(),
            committedAmount: $budget->getCommittedAmount(),
            severity: $severity,
            triggeredAt: new \DateTimeImmutable()
        );

        // Publish event
        $this->eventDispatcher->dispatch(new BudgetUtilizationAlertEvent(
            budgetId: $budget->getId(),
            periodId: $budget->getPeriodId(),
            utilizationPercentage: $utilizationPct,
            severity: $severity,
            message: "Budget {$budget->getName()} utilization at {$utilizationPct}%"
        ));

        // Send notification
        $this->sendNotification($alert, $budget);

        // Log alert
        $this->auditLogger->log(
            $budget->getId(),
            'utilization_alert',
            "Utilization alert: {$severity->value} severity at {$utilizationPct}%"
        );

        $this->logger->warning('Budget utilization alert triggered', [
            'budget_id' => $budget->getId(),
            'utilization_percentage' => $utilizationPct,
            'severity' => $severity->value,
        ]);

        return $alert;
    }

    /**
     * Send notification via Notifier package
     */
    private function sendNotification(UtilizationAlert $alert, object $budget): void
    {
        $departmentId = $budget->getDepartmentId();
        if (!$departmentId) {
            return;
        }

        // Determine notification channels based on severity
        $channels = $this->getNotificationChannels($alert->severity);

        $message = $this->buildNotificationMessage($alert);

        try {
            $this->notificationManager->send(
                recipients: $this->getRecipients($departmentId),
                subject: "Budget Utilization Alert: {$alert->budgetName}",
                message: $message,
                channels: $channels,
                priority: $alert->severity->getNotificationPriority(),
                context: [
                    'budget_id' => $alert->budgetId,
                    'period_id' => $alert->periodId,
                    'utilization_percentage' => $alert->utilizationPercentage,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to send utilization alert notification', [
                'budget_id' => $alert->budgetId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get notification channels based on severity
     */
    private function getNotificationChannels(AlertSeverity $severity): array
    {
        return match ($severity) {
            AlertSeverity::Critical => ['email', 'sms', 'slack'],
            AlertSeverity::High => ['email', 'slack'],
            AlertSeverity::Medium => ['email'],
            AlertSeverity::Low => ['email'],
        };
    }

    /**
     * Build notification message
     */
    private function buildNotificationMessage(UtilizationAlert $alert): string
    {
        $emoji = match ($alert->severity) {
            AlertSeverity::Critical => '🚨',
            AlertSeverity::High => '⚠️',
            AlertSeverity::Medium => '📊',
            AlertSeverity::Low => 'ℹ️',
        };

        return <<<MSG
{$emoji} Budget Utilization Alert - {$alert->severity->value} Severity

Budget: {$alert->budgetName}
Current Utilization: {$alert->utilizationPercentage}%

Allocated: {$alert->allocatedAmount}
Actual Spent: {$alert->actualAmount}
Committed: {$alert->committedAmount}

Please review budget consumption and take appropriate action.
MSG;
    }

    /**
     * Get notification recipients for department
     */
    private function getRecipients(string $departmentId): array
    {
        // This would integrate with HRM/Party package to get department managers
        // For now, return placeholder
        return [
            ['type' => 'department_head', 'department_id' => $departmentId],
        ];
    }

    /**
     * Periodic check for all active budgets in a period
     */
    public function performPeriodicCheck(string $periodId): array
    {
        $budgets = $this->budgetRepository->findByPeriod($periodId);
        $alerts = [];

        foreach ($budgets as $budget) {
            try {
                $alert = $this->checkUtilization($budget->getId());
                if ($alert !== null) {
                    $alerts[] = $alert;
                }
            } catch (\Exception $e) {
                $this->logger->error('Utilization check failed', [
                    'budget_id' => $budget->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Periodic utilization check completed', [
            'period_id' => $periodId,
            'budgets_checked' => count($budgets),
            'alerts_triggered' => count($alerts),
        ]);

        return $alerts;
    }

    /**
     * Get utilization summary for department
     */
    public function getDepartmentUtilizationSummary(string $departmentId, string $periodId): array
    {
        try {
            $consolidation = $this->analyticsRepository->getConsolidatedBudget($departmentId, $periodId);
            
            return [
                'department_id' => $departmentId,
                'period_id' => $periodId,
                'total_allocated' => (string) $consolidation->totalAllocatedAmount,
                'total_actual' => (string) $consolidation->totalActualAmount,
                'total_committed' => (string) $consolidation->totalCommittedAmount,
                'utilization_percentage' => $consolidation->utilizationPercentage,
                'budget_count' => $consolidation->budgetCount,
                'over_budget_count' => $consolidation->overBudgetCount,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get department utilization summary', [
                'department_id' => $departmentId,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
}
