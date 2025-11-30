<?php

declare(strict_types=1);

namespace Nexus\Budget\Services;

use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetAnalyticsRepositoryInterface;
use Nexus\Budget\Contracts\BudgetApprovalWorkflowInterface;
use Nexus\Budget\Enums\BudgetStatus;
use Nexus\Budget\Enums\VarianceInvestigationStatus;
use Nexus\Budget\Events\BudgetVarianceThresholdExceededEvent;
use Nexus\Budget\ValueObjects\BudgetVariance;
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Budget Variance Investigator
 * 
 * Handles variance investigation workflow when thresholds are exceeded.
 * Routes significant variances to approval workflow for management review.
 */
final readonly class BudgetVarianceInvestigator
{
    public function __construct(
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetAnalyticsRepositoryInterface $analyticsRepository,
        private BudgetApprovalWorkflowInterface $workflowService,
        private SettingsManagerInterface $settings,
        private AuditLoggerInterface $auditLogger,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    /**
     * Analyze variance and trigger investigation if needed
     */
    public function analyzeVariance(string $budgetId, BudgetVariance $variance): void
    {
        $threshold = $this->settings->getFloat('budget.variance_investigation_threshold_percentage', 15.0);
        
        if (!$this->requiresInvestigation($variance, $threshold)) {
            return;
        }

        $this->triggerInvestigation($budgetId, $variance);
    }

    /**
     * Check if variance requires investigation
     */
    private function requiresInvestigation(BudgetVariance $variance, float $threshold): bool
    {
        // Use the value object's built-in logic
        return $variance->requiresInvestigation($threshold);
    }

    /**
     * Trigger investigation workflow
     */
    private function triggerInvestigation(string $budgetId, BudgetVariance $variance): void
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            return;
        }

        // Update status to under investigation
        $this->budgetRepository->updateStatus($budgetId, BudgetStatus::UnderInvestigation);

        // Publish event
        $this->eventDispatcher->dispatch(new BudgetVarianceThresholdExceededEvent(
            budgetId: $budgetId,
            periodId: $budget->getPeriodId(),
            allocatedAmount: $variance->allocatedAmount,
            actualAmount: $variance->actualAmount,
            varianceAmount: $variance->variance,
            variancePercentage: $variance->variancePercentage
        ));

        // Request workflow approval with investigation details
        $this->workflowService->requestVarianceInvestigation(
            $budgetId,
            $variance->variance,
            $this->buildInvestigationContext($budget, $variance)
        );

        $this->auditLogger->log(
            $budgetId,
            'variance_investigation_triggered',
            "Variance investigation triggered: {$variance->variancePercentage}% variance ({$variance->variance})"
        );

        $this->logger->warning('Budget variance investigation triggered', [
            'budget_id' => $budgetId,
            'variance_percentage' => $variance->variancePercentage,
            'variance_amount' => (string) $variance->variance,
        ]);
    }

    /**
     * Build investigation context for workflow
     */
    private function buildInvestigationContext(object $budget, BudgetVariance $variance): array
    {
        $departmentId = $budget->getDepartmentId();
        
        // Get historical performance
        $historicalPerformance = $departmentId 
            ? $this->analyticsRepository->getDepartmentVarianceHistory($departmentId, 12)
            : [];

        return [
            'budget_name' => $budget->getName(),
            'budget_type' => $budget->getType()->value,
            'department_id' => $departmentId,
            'project_id' => $budget->getProjectId(),
            'allocated_amount' => (string) $variance->allocatedAmount,
            'actual_amount' => (string) $variance->actualAmount,
            'variance_amount' => (string) $variance->variance,
            'variance_percentage' => $variance->variancePercentage,
            'is_overbudget' => $variance->isOverBudget(),
            'is_revenue_budget' => $variance->isRevenueBudget,
            'historical_performance' => $historicalPerformance,
        ];
    }

    /**
     * Periodic variance analysis for all active budgets
     */
    public function performPeriodicAnalysis(string $periodId): array
    {
        $budgets = $this->budgetRepository->findByPeriod($periodId);
        $investigationResults = [];

        foreach ($budgets as $budget) {
            if (!$budget->getStatus()->canModify()) {
                continue; // Skip closed/locked budgets
            }

            try {
                $variance = new BudgetVariance(
                    budgetId: $budget->getId(),
                    allocatedAmount: $budget->getAllocatedAmount(),
                    actualAmount: $budget->getActualAmount(),
                    variance: $budget->getAllocatedAmount()->subtract($budget->getActualAmount()),
                    variancePercentage: $this->calculateVariancePercentage($budget),
                    isRevenueBudget: $budget->isRevenueBudget()
                );

                $this->analyzeVariance($budget->getId(), $variance);
                
                $investigationResults[] = [
                    'budget_id' => $budget->getId(),
                    'variance' => $variance,
                    'requires_investigation' => $variance->requiresInvestigation(
                        $this->settings->getFloat('budget.variance_investigation_threshold_percentage', 15.0)
                    ),
                ];
            } catch (\Exception $e) {
                $this->logger->error('Variance analysis failed', [
                    'budget_id' => $budget->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $investigationResults;
    }

    /**
     * Calculate variance percentage
     */
    private function calculateVariancePercentage(object $budget): float
    {
        $allocated = $budget->getAllocatedAmount()->getAmount();
        if ($allocated == 0) {
            return 0.0;
        }

        $variance = $allocated - $budget->getActualAmount()->getAmount();
        return ($variance / $allocated) * 100;
    }

    /**
     * Resolve investigation (called after workflow approval)
     */
    public function resolveInvestigation(
        string $budgetId,
        VarianceInvestigationStatus $resolution,
        string $notes
    ): void {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            return;
        }

        // Update status based on resolution
        $newStatus = match ($resolution) {
            VarianceInvestigationStatus::Approved => BudgetStatus::Active,
            VarianceInvestigationStatus::RequiresAmendment => BudgetStatus::Draft,
            VarianceInvestigationStatus::Rejected => BudgetStatus::Locked,
        };

        $this->budgetRepository->updateStatus($budgetId, $newStatus);

        $this->auditLogger->log(
            $budgetId,
            'variance_investigation_resolved',
            "Investigation resolved: {$resolution->value} - {$notes}"
        );

        $this->logger->info('Variance investigation resolved', [
            'budget_id' => $budgetId,
            'resolution' => $resolution->value,
            'new_status' => $newStatus->value,
        ]);
    }
}
