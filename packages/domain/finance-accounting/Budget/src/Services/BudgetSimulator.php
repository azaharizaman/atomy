<?php

declare(strict_types=1);

namespace Nexus\Budget\Services;

use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetSimulatorInterface;
use Nexus\Budget\Enums\BudgetStatus;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Budget\Exceptions\BudgetNotFoundException;
use Nexus\Budget\Exceptions\SimulationNotEditableException;
use Nexus\Budget\ValueObjects\BudgetSimulationResult;
use Nexus\Budget\ValueObjects\BudgetAllocation;
use Nexus\Finance\ValueObjects\Money;
use Psr\Log\LoggerInterface;

/**
 * Budget Simulator
 * 
 * Creates "what-if" simulations for budget scenarios.
 * Simulations are read-only copies that allow testing different allocation
 * strategies without affecting real budgets.
 */
final readonly class BudgetSimulator implements BudgetSimulatorInterface
{
    public function __construct(
        private BudgetRepositoryInterface $budgetRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Create a simulation from an existing budget
     */
    public function createSimulation(string $baseBudgetId): BudgetInterface
    {
        $baseBudget = $this->budgetRepository->findById($baseBudgetId);
        if (!$baseBudget) {
            throw new BudgetNotFoundException($baseBudgetId);
        }

        // Create simulation with copied attributes
        $allocation = new BudgetAllocation(
            name: "[SIMULATION] {$baseBudget->getName()}",
            periodId: $baseBudget->getPeriodId(),
            budgetType: $baseBudget->getType(),
            allocatedAmount: $baseBudget->getAllocatedAmount(),
            currency: $baseBudget->getCurrency(),
            departmentId: $baseBudget->getDepartmentId(),
            projectId: $baseBudget->getProjectId(),
            accountId: $baseBudget->getAccountId(),
            parentBudgetId: null, // Simulations don't participate in hierarchy
            rolloverPolicy: $baseBudget->getRolloverPolicy(),
            justification: "Simulation based on budget {$baseBudgetId}"
        );

        $simulation = $this->budgetRepository->createSimulation($baseBudgetId, $allocation);

        $this->logger->info('Budget simulation created', [
            'simulation_id' => $simulation->getId(),
            'base_budget_id' => $baseBudgetId,
        ]);

        return $simulation;
    }

    /**
     * Run simulation with various scenarios
     */
    public function runSimulation(
        string $simulationId,
        array $scenarios
    ): BudgetSimulationResult {
        $simulation = $this->budgetRepository->findById($simulationId);
        if (!$simulation) {
            throw new BudgetNotFoundException($simulationId);
        }

        if ($simulation->getStatus() !== BudgetStatus::Simulated) {
            throw new SimulationNotEditableException($simulationId);
        }

        $results = [];

        foreach ($scenarios as $scenario) {
            $scenarioResult = $this->runScenario($simulation, $scenario);
            $results[$scenario['name']] = $scenarioResult;
        }

        $recommendation = $this->generateRecommendation($results);

        return new BudgetSimulationResult(
            simulationId: $simulationId,
            baseBudgetId: $simulation->getId(), // In real impl, would track base
            scenarios: $results,
            recommendation: $recommendation,
            createdAt: new \DateTimeImmutable()
        );
    }

    /**
     * Run a single scenario
     */
    private function runScenario(BudgetInterface $simulation, array $scenario): array
    {
        $allocatedAmount = $scenario['allocated_amount'] ?? $simulation->getAllocatedAmount()->getAmount();
        $projectedSpending = $scenario['projected_spending'] ?? 0.0;
        $commitments = $scenario['commitments'] ?? [];

        // Calculate metrics
        $totalCommitments = array_sum($commitments);
        $available = $allocatedAmount - $totalCommitments - $projectedSpending;
        $utilizationPct = $allocatedAmount > 0 
            ? (($projectedSpending + $totalCommitments) / $allocatedAmount) * 100 
            : 0.0;

        $willExceed = $available < 0;
        $variance = $allocatedAmount - $projectedSpending;
        $variancePct = $allocatedAmount > 0 ? ($variance / $allocatedAmount) * 100 : 0.0;

        return [
            'allocated_amount' => $allocatedAmount,
            'projected_spending' => $projectedSpending,
            'commitments' => $totalCommitments,
            'available_amount' => $available,
            'utilization_percentage' => $utilizationPct,
            'will_exceed_budget' => $willExceed,
            'variance' => $variance,
            'variance_percentage' => $variancePct,
        ];
    }

    /**
     * Compare multiple simulations
     */
    public function compareSimulations(array $simulationIds): array
    {
        $comparisons = [];

        foreach ($simulationIds as $simulationId) {
            $simulation = $this->budgetRepository->findById($simulationId);
            if (!$simulation) {
                continue;
            }

            $comparisons[] = [
                'simulation_id' => $simulationId,
                'name' => $simulation->getName(),
                'allocated_amount' => (string) $simulation->getAllocatedAmount(),
                'committed_amount' => (string) $simulation->getCommittedAmount(),
                'actual_amount' => (string) $simulation->getActualAmount(),
                'available_amount' => (string) $simulation->getAvailableAmount(),
                'utilization_percentage' => $this->calculateUtilization($simulation),
            ];
        }

        return $comparisons;
    }

    /**
     * Apply simulation to real budget (convert simulation to active budget)
     */
    public function applySimulation(string $simulationId, string $targetBudgetId): void
    {
        $simulation = $this->budgetRepository->findById($simulationId);
        $targetBudget = $this->budgetRepository->findById($targetBudgetId);

        if (!$simulation || !$targetBudget) {
            throw new BudgetNotFoundException($simulationId);
        }

        if ($simulation->getStatus() !== BudgetStatus::Simulated) {
            throw new SimulationNotEditableException($simulationId);
        }

        // Copy allocation from simulation to target
        $allocation = new BudgetAllocation(
            name: $targetBudget->getName(),
            periodId: $targetBudget->getPeriodId(),
            budgetType: $targetBudget->getType(),
            allocatedAmount: $simulation->getAllocatedAmount(),
            currency: $targetBudget->getCurrency(),
            departmentId: $targetBudget->getDepartmentId(),
            projectId: $targetBudget->getProjectId(),
            accountId: $targetBudget->getAccountId(),
            parentBudgetId: $targetBudget->getParentBudgetId(),
            rolloverPolicy: $targetBudget->getRolloverPolicy(),
            justification: "Applied simulation {$simulationId}"
        );

        $this->budgetRepository->updateAllocation($targetBudgetId, $allocation);

        $this->logger->info('Simulation applied to budget', [
            'simulation_id' => $simulationId,
            'target_budget_id' => $targetBudgetId,
        ]);
    }

    /**
     * Delete simulation
     */
    public function deleteSimulation(string $simulationId): void
    {
        $simulation = $this->budgetRepository->findById($simulationId);
        if (!$simulation) {
            return;
        }

        if ($simulation->getStatus() !== BudgetStatus::Simulated) {
            throw new SimulationNotEditableException($simulationId);
        }

        $this->budgetRepository->delete($simulationId);

        $this->logger->info('Simulation deleted', ['simulation_id' => $simulationId]);
    }

    /**
     * Calculate utilization percentage
     */
    private function calculateUtilization(BudgetInterface $budget): float
    {
        $allocated = $budget->getAllocatedAmount()->getAmount();
        if ($allocated == 0) {
            return 0.0;
        }

        $actual = $budget->getActualAmount()->getAmount();
        return ($actual / $allocated) * 100;
    }

    /**
     * Generate recommendation from scenario results
     */
    private function generateRecommendation(array $results): string
    {
        $bestScenario = null;
        $bestVariance = PHP_FLOAT_MAX;

        foreach ($results as $scenarioName => $result) {
            $absVariance = abs($result['variance_percentage']);
            
            // Find scenario closest to budget without exceeding
            if (!$result['will_exceed_budget'] && $absVariance < $bestVariance) {
                $bestVariance = $absVariance;
                $bestScenario = $scenarioName;
            }
        }

        if ($bestScenario === null) {
            return "All scenarios exceed budget. Consider increasing allocation.";
        }

        return "Recommended scenario: {$bestScenario} (variance: " . 
               number_format($results[$bestScenario]['variance_percentage'], 2) . "%)";
    }
}
