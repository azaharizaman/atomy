<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\ValueObjects\CapacityResolutionSuggestion;

/**
 * Capacity Resolver interface.
 *
 * Provides intelligent suggestions for resolving capacity constraints
 * when demand exceeds available capacity.
 */
interface CapacityResolverInterface
{
    /**
     * Get resolution suggestions for a capacity constraint.
     *
     * Returns prioritized list of actionable suggestions.
     *
     * @param string $workCenterId Overloaded work center
     * @param \DateTimeImmutable $constraintDate Date of constraint
     * @param float $overloadHours Hours of overload
     * @return array<CapacityResolutionSuggestion>
     */
    public function getSuggestions(
        string $workCenterId,
        \DateTimeImmutable $constraintDate,
        float $overloadHours
    ): array;

    /**
     * Suggest alternative work centers.
     *
     * Finds work centers that can perform the same operations
     * with available capacity.
     *
     * @param string $workCenterId Original work center
     * @param \DateTimeImmutable $date Required date
     * @param float $requiredHours Hours needed
     * @return array<CapacityResolutionSuggestion>
     */
    public function suggestAlternativeWorkCenters(
        string $workCenterId,
        \DateTimeImmutable $date,
        float $requiredHours
    ): array;

    /**
     * Suggest overtime options.
     *
     * Calculates how much overtime would resolve the constraint.
     *
     * @param string $workCenterId Work center
     * @param \DateTimeImmutable $date Constraint date
     * @param float $overloadHours Hours of overload
     * @return array<CapacityResolutionSuggestion>
     */
    public function suggestOvertime(
        string $workCenterId,
        \DateTimeImmutable $date,
        float $overloadHours
    ): array;

    /**
     * Suggest order rescheduling.
     *
     * Identifies orders that could be moved to off-peak periods.
     *
     * @param string $workCenterId Work center
     * @param \DateTimeImmutable $constraintStart Start of constraint period
     * @param \DateTimeImmutable $constraintEnd End of constraint period
     * @return array<CapacityResolutionSuggestion>
     */
    public function suggestReschedule(
        string $workCenterId,
        \DateTimeImmutable $constraintStart,
        \DateTimeImmutable $constraintEnd
    ): array;

    /**
     * Suggest subcontracting.
     *
     * Identifies operations that could be subcontracted.
     *
     * @param string $workCenterId Work center
     * @param float $overloadHours Hours of overload
     * @return array<CapacityResolutionSuggestion>
     */
    public function suggestSubcontracting(
        string $workCenterId,
        float $overloadHours
    ): array;

    /**
     * Suggest order splitting.
     *
     * Identifies orders that could be split across multiple periods/work centers.
     *
     * @param string $workCenterId Work center
     * @param \DateTimeImmutable $date Constraint date
     * @return array<CapacityResolutionSuggestion>
     */
    public function suggestOrderSplitting(
        string $workCenterId,
        \DateTimeImmutable $date
    ): array;

    /**
     * Auto-resolve capacity constraints.
     *
     * Attempts to automatically apply the highest-priority feasible suggestions.
     * Returns list of actions taken.
     *
     * @param string $workCenterId Work center
     * @param \DateTimeImmutable $date Constraint date
     * @param bool $simulate If true, returns suggestions without applying
     * @return array{resolved: bool, actions: array<CapacityResolutionSuggestion>}
     */
    public function autoResolve(
        string $workCenterId,
        \DateTimeImmutable $date,
        bool $simulate = false
    ): array;

    /**
     * Set resolution preferences.
     *
     * Configure which resolution types to consider and their priority.
     *
     * @param array<string, int> $preferences Action type => priority map (higher = preferred)
     */
    public function setPreferences(array $preferences): void;

    /**
     * Get current resolution preferences.
     *
     * @return array<string, int>
     */
    public function getPreferences(): array;
}
