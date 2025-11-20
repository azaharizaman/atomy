<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

use Nexus\Finance\ValueObjects\Money;

/**
 * Budget Simulation Result value object
 * 
 * Immutable representation of budget simulation results.
 */
final readonly class BudgetSimulationResult
{
    /**
     * @param string $scenarioName Scenario name
     * @param Money $projectedAvailable Projected available amount
     * @param array<string, mixed> $comparisonMetrics Comparison metrics
     * @param array<string> $recommendations Recommendations
     */
    public function __construct(
        private string $scenarioName,
        private Money $projectedAvailable,
        private array $comparisonMetrics,
        private array $recommendations
    ) {}

    public function getScenarioName(): string
    {
        return $this->scenarioName;
    }

    public function getProjectedAvailable(): Money
    {
        return $this->projectedAvailable;
    }

    /**
     * @return array<string, mixed>
     */
    public function getComparisonMetrics(): array
    {
        return $this->comparisonMetrics;
    }

    /**
     * @return array<string>
     */
    public function getRecommendations(): array
    {
        return $this->recommendations;
    }

    /**
     * Get metric by key
     */
    public function getMetric(string $key): mixed
    {
        return $this->comparisonMetrics[$key] ?? null;
    }

    /**
     * Check if scenario is viable
     */
    public function isViable(): bool
    {
        return $this->projectedAvailable->getAmount() >= 0;
    }
}
