<?php

declare(strict_types=1);

namespace Nexus\Budget\Services;

use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetApprovalWorkflowInterface;
use Nexus\Budget\Enums\RolloverPolicy;
use Nexus\Budget\Enums\BudgetStatus;
use Nexus\Budget\ValueObjects\BudgetAllocation;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Budget Rollover Handler
 * 
 * Handles automatic budget rollovers at period close based on policy.
 */
final readonly class BudgetRolloverHandler
{
    public function __construct(
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetApprovalWorkflowInterface $workflowService,
        private PeriodManagerInterface $periodManager,
        private SettingsManagerInterface $settings,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    /**
     * Process rollover for all budgets in a closing period
     */
    public function processRollover(string $closingPeriodId): void
    {
        $closingPeriod = $this->periodManager->findById($closingPeriodId);
        if (!$closingPeriod) {
            throw new \InvalidArgumentException("Period not found: {$closingPeriodId}");
        }

        // Get next period
        $nextPeriod = $this->periodManager->getNextPeriod($closingPeriodId);
        if (!$nextPeriod) {
            $this->logger->warning('No next period found for rollover', [
                'closing_period_id' => $closingPeriodId,
            ]);
            return;
        }

        // Get all budgets in closing period
        $budgets = $this->budgetRepository->findByPeriod($closingPeriodId);

        foreach ($budgets as $budget) {
            try {
                $this->rolloverBudget($budget->getId(), $nextPeriod->getId());
            } catch (\Exception $e) {
                $this->logger->error('Budget rollover failed', [
                    'budget_id' => $budget->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Rollover a single budget to next period
     */
    private function rolloverBudget(string $budgetId, string $nextPeriodId): void
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            return;
        }

        $policy = $budget->getRolloverPolicy();

        match ($policy) {
            RolloverPolicy::Expire => $this->handleExpire($budget->getId()),
            RolloverPolicy::AutoRollUnused => $this->handleAutoRoll($budget->getId(), $nextPeriodId),
            RolloverPolicy::RequireApproval => $this->handleRequireApproval($budget->getId(), $nextPeriodId),
        };
    }

    /**
     * Handle Expire policy - mark budget as closed
     */
    private function handleExpire(string $budgetId): void
    {
        $this->budgetRepository->updateStatus($budgetId, BudgetStatus::Closed);
        
        $this->auditLogger->log(
            $budgetId,
            'budget_expired',
            'Budget expired at period close (Expire policy)'
        );

        $this->logger->info('Budget expired', ['budget_id' => $budgetId]);
    }

    /**
     * Handle AutoRollUnused policy - automatically create next period budget
     */
    private function handleAutoRoll(string $budgetId, string $nextPeriodId): void
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            return;
        }

        // Calculate unused amount
        $unusedAmount = $budget->getAvailableAmount();

        // Create new budget for next period
        $newAllocation = new BudgetAllocation(
            name: $budget->getName(),
            periodId: $nextPeriodId,
            budgetType: $budget->getType(),
            allocatedAmount: $unusedAmount,
            currency: $budget->getCurrency(),
            departmentId: $budget->getDepartmentId(),
            projectId: $budget->getProjectId(),
            accountId: $budget->getAccountId(),
            parentBudgetId: $budget->getParentBudgetId(),
            rolloverPolicy: $budget->getRolloverPolicy(),
            justification: "Auto-rollover from budget {$budgetId}"
        );

        $newBudget = $this->budgetRepository->create($newAllocation);

        // Mark old budget as closed
        $this->budgetRepository->updateStatus($budgetId, BudgetStatus::Closed);

        $this->auditLogger->log(
            $budgetId,
            'budget_rolled_over',
            "Budget auto-rolled to {$newBudget->getId()} with unused amount {$unusedAmount}"
        );

        $this->logger->info('Budget auto-rolled', [
            'old_budget_id' => $budgetId,
            'new_budget_id' => $newBudget->getId(),
            'rolled_amount' => (string) $unusedAmount,
        ]);
    }

    /**
     * Handle RequireApproval policy - route to workflow
     */
    private function handleRequireApproval(string $budgetId, string $nextPeriodId): void
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            return;
        }

        $unusedAmount = $budget->getAvailableAmount();

        // Request workflow approval
        $this->workflowService->requestBudgetRolloverApproval(
            $budgetId,
            $nextPeriodId,
            $unusedAmount
        );

        $this->auditLogger->log(
            $budgetId,
            'rollover_approval_requested',
            "Rollover approval requested for {$unusedAmount} to next period"
        );

        $this->logger->info('Rollover approval requested', [
            'budget_id' => $budgetId,
            'next_period_id' => $nextPeriodId,
            'amount' => (string) $unusedAmount,
        ]);
    }
}
