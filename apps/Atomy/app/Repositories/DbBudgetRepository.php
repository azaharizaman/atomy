<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Budget;
use App\Models\BudgetRevision;
use Illuminate\Support\Str;
use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Enums\BudgetStatus;
use Nexus\Budget\ValueObjects\BudgetAllocation;
use Nexus\Currency\ValueObjects\Money;

/**
 * Database Budget Repository
 * 
 * Laravel/Eloquent implementation of BudgetRepositoryInterface.
 */
final class DbBudgetRepository implements BudgetRepositoryInterface
{
    public function __construct(
        private readonly Budget $model
    ) {}

    public function findById(string $id): ?BudgetInterface
    {
        return $this->model->find($id);
    }

    public function findByPeriod(string $periodId): array
    {
        return $this->model
            ->where('period_id', $periodId)
            ->where('is_simulation', false)
            ->get()
            ->all();
    }

    public function findByDepartment(string $departmentId, string $periodId): array
    {
        return $this->model
            ->where('department_id', $departmentId)
            ->where('period_id', $periodId)
            ->where('is_simulation', false)
            ->get()
            ->all();
    }

    public function findDescendants(string $budgetId): array
    {
        // Get all child budgets recursively using CTE
        $descendants = \DB::select("
            WITH RECURSIVE budget_tree AS (
                SELECT * FROM budgets WHERE id = ?
                UNION ALL
                SELECT b.* FROM budgets b
                INNER JOIN budget_tree bt ON b.parent_budget_id = bt.id
            )
            SELECT * FROM budget_tree WHERE id != ?
        ", [$budgetId, $budgetId]);

        // Hydrate into Budget models
        return array_map(
            fn($record) => $this->model->newFromBuilder((array) $record),
            $descendants
        );
    }

    public function getHierarchyDepth(string $budgetId): int
    {
        $budget = $this->model->find($budgetId);
        if (!$budget) {
            return 0;
        }

        return $budget->hierarchy_level;
    }

    public function create(BudgetAllocation $allocation): BudgetInterface
    {
        $budget = $this->model->create([
            'id' => Str::ulid()->toString(),
            'name' => $allocation->name,
            'period_id' => $allocation->periodId,
            'budget_type' => $allocation->budgetType->value,
            'status' => BudgetStatus::Draft->value,
            'allocated_amount_functional' => $allocation->allocatedAmount->getAmount(),
            'functional_currency' => $allocation->currency,
            'allocated_amount_presentation' => null,
            'presentation_currency' => null,
            'exchange_rate_snapshot' => null,
            'committed_amount' => 0,
            'actual_amount' => 0,
            'department_id' => $allocation->departmentId,
            'project_id' => $allocation->projectId,
            'account_id' => $allocation->accountId,
            'parent_budget_id' => $allocation->parentBudgetId,
            'hierarchy_level' => $this->calculateHierarchyLevel($allocation->parentBudgetId),
            'rollover_policy' => $allocation->rolloverPolicy->value,
            'budgeting_methodology' => 'incremental', // Default
            'base_budget_id' => null,
            'is_simulation' => false,
            'created_by' => null, // Would come from auth context
        ]);

        // Create initial revision
        $this->createRevision($budget->id, [
            'previous_allocated_amount' => 0,
            'new_allocated_amount' => $allocation->allocatedAmount->getAmount(),
            'currency' => $allocation->currency,
            'previous_status' => BudgetStatus::Draft->value,
            'new_status' => BudgetStatus::Draft->value,
            'change_type' => 'creation',
            'reason' => 'Budget created',
            'justification' => $allocation->justification,
        ]);

        return $budget;
    }

    public function update(string $id, array $data): void
    {
        $budget = $this->model->find($id);
        if (!$budget) {
            return;
        }

        $budget->update($data);
    }

    public function updateAllocation(string $id, BudgetAllocation $allocation): void
    {
        $budget = $this->model->find($id);
        if (!$budget) {
            return;
        }

        $previousAmount = $budget->allocated_amount_functional;

        $budget->update([
            'allocated_amount_functional' => $allocation->allocatedAmount->getAmount(),
            'updated_by' => null, // Would come from auth context
        ]);

        // Create revision
        $this->createRevision($id, [
            'previous_allocated_amount' => $previousAmount,
            'new_allocated_amount' => $allocation->allocatedAmount->getAmount(),
            'currency' => $allocation->currency,
            'previous_status' => $budget->status->value,
            'new_status' => $budget->status->value,
            'change_type' => 'allocation_update',
            'reason' => 'Budget allocation updated',
            'justification' => $allocation->justification,
        ]);
    }

    public function updateStatus(string $id, BudgetStatus $status): void
    {
        $budget = $this->model->find($id);
        if (!$budget) {
            return;
        }

        $previousStatus = $budget->status;

        $budget->update([
            'status' => $status->value,
            'updated_by' => null, // Would come from auth context
        ]);

        // Create revision
        $this->createRevision($id, [
            'previous_allocated_amount' => $budget->allocated_amount_functional,
            'new_allocated_amount' => $budget->allocated_amount_functional,
            'currency' => $budget->functional_currency,
            'previous_status' => $previousStatus->value,
            'new_status' => $status->value,
            'change_type' => 'status_change',
            'reason' => "Status changed from {$previousStatus->value} to {$status->value}",
        ]);
    }

    public function transferAllocation(string $fromBudgetId, string $toBudgetId, Money $amount): void
    {
        \DB::transaction(function () use ($fromBudgetId, $toBudgetId, $amount) {
            $fromBudget = $this->model->find($fromBudgetId);
            $toBudget = $this->model->find($toBudgetId);

            if (!$fromBudget || !$toBudget) {
                return;
            }

            // Deduct from source
            $fromBudget->update([
                'allocated_amount_functional' => $fromBudget->allocated_amount_functional - $amount->getAmount(),
            ]);

            // Add to destination
            $toBudget->update([
                'allocated_amount_functional' => $toBudget->allocated_amount_functional + $amount->getAmount(),
            ]);

            // Create revisions for both
            $this->createRevision($fromBudgetId, [
                'previous_allocated_amount' => $fromBudget->allocated_amount_functional + $amount->getAmount(),
                'new_allocated_amount' => $fromBudget->allocated_amount_functional,
                'currency' => $fromBudget->functional_currency,
                'previous_status' => $fromBudget->status->value,
                'new_status' => $fromBudget->status->value,
                'change_type' => 'transfer_out',
                'reason' => "Transferred {$amount} to budget {$toBudgetId}",
                'related_budget_id' => $toBudgetId,
            ]);

            $this->createRevision($toBudgetId, [
                'previous_allocated_amount' => $toBudget->allocated_amount_functional - $amount->getAmount(),
                'new_allocated_amount' => $toBudget->allocated_amount_functional,
                'currency' => $toBudget->functional_currency,
                'previous_status' => $toBudget->status->value,
                'new_status' => $toBudget->status->value,
                'change_type' => 'transfer_in',
                'reason' => "Received {$amount} from budget {$fromBudgetId}",
                'related_budget_id' => $fromBudgetId,
            ]);
        });
    }

    public function amendAllocation(string $id, Money $newAllocation): void
    {
        $budget = $this->model->find($id);
        if (!$budget) {
            return;
        }

        $previousAmount = $budget->allocated_amount_functional;

        $budget->update([
            'allocated_amount_functional' => $newAllocation->getAmount(),
        ]);

        $this->createRevision($id, [
            'previous_allocated_amount' => $previousAmount,
            'new_allocated_amount' => $newAllocation->getAmount(),
            'currency' => $budget->functional_currency,
            'previous_status' => $budget->status->value,
            'new_status' => $budget->status->value,
            'change_type' => 'amendment',
            'reason' => 'Budget amended',
        ]);
    }

    public function createSimulation(string $baseBudgetId, ?BudgetAllocation $allocation = null): BudgetInterface
    {
        $baseBudget = $this->model->find($baseBudgetId);
        if (!$baseBudget) {
            throw new \InvalidArgumentException("Base budget not found: {$baseBudgetId}");
        }

        $simulation = $this->model->create([
            'id' => Str::ulid()->toString(),
            'name' => $allocation?->name ?? "[SIMULATION] {$baseBudget->name}",
            'period_id' => $baseBudget->period_id,
            'budget_type' => $baseBudget->budget_type->value,
            'status' => BudgetStatus::Simulated->value,
            'allocated_amount_functional' => $allocation?->allocatedAmount->getAmount() ?? $baseBudget->allocated_amount_functional,
            'functional_currency' => $baseBudget->functional_currency,
            'allocated_amount_presentation' => null,
            'presentation_currency' => null,
            'exchange_rate_snapshot' => null,
            'committed_amount' => 0,
            'actual_amount' => 0,
            'department_id' => $baseBudget->department_id,
            'project_id' => $baseBudget->project_id,
            'account_id' => $baseBudget->account_id,
            'parent_budget_id' => null, // Simulations don't participate in hierarchy
            'hierarchy_level' => 0,
            'rollover_policy' => $baseBudget->rollover_policy->value,
            'budgeting_methodology' => $baseBudget->budgeting_methodology->value,
            'base_budget_id' => $baseBudgetId,
            'is_simulation' => true,
        ]);

        return $simulation;
    }

    public function delete(string $id): void
    {
        $budget = $this->model->find($id);
        if ($budget) {
            $budget->delete();
        }
    }

    /**
     * Calculate hierarchy level based on parent
     */
    private function calculateHierarchyLevel(?string $parentBudgetId): int
    {
        if ($parentBudgetId === null) {
            return 0;
        }

        $parent = $this->model->find($parentBudgetId);
        return $parent ? $parent->hierarchy_level + 1 : 0;
    }

    /**
     * Create budget revision record
     */
    private function createRevision(string $budgetId, array $data): void
    {
        $lastRevision = BudgetRevision::where('budget_id', $budgetId)
            ->orderBy('revision_number', 'desc')
            ->first();

        BudgetRevision::create([
            'id' => Str::ulid()->toString(),
            'budget_id' => $budgetId,
            'revision_number' => $lastRevision ? $lastRevision->revision_number + 1 : 1,
            'previous_allocated_amount' => $data['previous_allocated_amount'],
            'new_allocated_amount' => $data['new_allocated_amount'],
            'currency' => $data['currency'],
            'previous_status' => $data['previous_status'],
            'new_status' => $data['new_status'],
            'change_type' => $data['change_type'],
            'reason' => $data['reason'],
            'justification' => $data['justification'] ?? null,
            'related_budget_id' => $data['related_budget_id'] ?? null,
            'workflow_approval_id' => $data['workflow_approval_id'] ?? null,
            'created_by' => null, // Would come from auth context
            'created_at' => now(),
        ]);
    }
}
