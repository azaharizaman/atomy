<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Budget\ValueObjects\BudgetSimulationResult;

/**
 * Budget Simulator service contract
 * 
 * Provides "what-if" scenario testing for budget planning.
 */
interface BudgetSimulatorInterface
{
    /**
     * Create simulation scenario
     * 
     * @param string $baseBudgetId Base budget identifier
     * @param string $scenarioName Scenario name
     * @param array<string, mixed> $modifications Scenario modifications
     * @return BudgetInterface
     */
    public function createScenario(
        string $baseBudgetId,
        string $scenarioName,
        array $modifications
    ): BudgetInterface;

    /**
     * Run simulation
     * 
     * @param string $simulationId Simulation budget identifier
     * @param array<string, mixed> $transactions Hypothetical transactions
     * @return BudgetSimulationResult
     */
    public function runSimulation(string $simulationId, array $transactions): BudgetSimulationResult;

    /**
     * Compare scenarios
     * 
     * @param array<string> $simulationIds Simulation identifiers
     * @return array<string, BudgetSimulationResult>
     */
    public function compareScenarios(array $simulationIds): array;

    /**
     * Apply scenario to create real budget
     * 
     * @param string $simulationId Simulation identifier
     * @return BudgetInterface
     */
    public function applyScenario(string $simulationId): BudgetInterface;
}
