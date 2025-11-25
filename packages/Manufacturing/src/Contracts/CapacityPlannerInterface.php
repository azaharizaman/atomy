<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\ValueObjects\CapacityLoad;
use Nexus\Manufacturing\ValueObjects\CapacityProfile;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;

/**
 * Capacity Planner interface.
 *
 * Provides capacity requirements planning (CRP) functionality
 * with configurable planning horizons and load analysis.
 */
interface CapacityPlannerInterface
{
    /**
     * Calculate capacity requirements for planned orders.
     *
     * @param array<array{orderId: string, productId: string, quantity: float, startDate: \DateTimeImmutable}> $plannedOrders
     * @return array<CapacityLoad>
     */
    public function calculateRequirements(array $plannedOrders): array;

    /**
     * Get capacity load profile for a work center.
     *
     * Shows load by time period across the planning horizon.
     *
     * @param string $workCenterId Work center to analyze
     * @param PlanningHorizon $horizon Planning horizon
     * @return CapacityProfile Capacity profile with load by period
     */
    public function getCapacityProfile(string $workCenterId, PlanningHorizon $horizon): CapacityProfile;

    /**
     * Get capacity load profile for all work centers.
     *
     * @param PlanningHorizon $horizon Planning horizon
     * @return array<string, CapacityProfile> Work center ID => profile map
     */
    public function getAllCapacityProfiles(PlanningHorizon $horizon): array;

    /**
     * Identify capacity bottlenecks.
     *
     * Finds work centers where load exceeds available capacity.
     *
     * @param PlanningHorizon $horizon Planning horizon
     * @param float $threshold Utilization threshold (e.g., 0.9 = 90%)
     * @return array<array{workCenterId: string, period: string, utilization: float, overload: float}>
     */
    public function identifyBottlenecks(PlanningHorizon $horizon, float $threshold = 0.9): array;

    /**
     * Check if capacity is available for an order.
     *
     * @param string $productId Product to produce
     * @param float $quantity Quantity to produce
     * @param \DateTimeImmutable $startDate Planned start date
     * @return array{available: bool, constrainedWorkCenters: array<string>}
     */
    public function checkAvailability(
        string $productId,
        float $quantity,
        \DateTimeImmutable $startDate
    ): array;

    /**
     * Find earliest available date for an order.
     *
     * Searches forward from the desired date until capacity is available.
     *
     * @param string $productId Product to produce
     * @param float $quantity Quantity to produce
     * @param \DateTimeImmutable $desiredDate Desired start date
     * @return \DateTimeImmutable Earliest available start date
     */
    public function findEarliestAvailable(
        string $productId,
        float $quantity,
        \DateTimeImmutable $desiredDate
    ): \DateTimeImmutable;

    /**
     * Level the load across work centers.
     *
     * Suggests order timing adjustments to balance load.
     *
     * @param array<array{orderId: string, productId: string, quantity: float, startDate: \DateTimeImmutable}> $orders
     * @return array<array{orderId: string, originalDate: \DateTimeImmutable, suggestedDate: \DateTimeImmutable}>
     */
    public function levelLoad(array $orders): array;

    /**
     * Set the planning horizon.
     */
    public function setPlanningHorizon(PlanningHorizon $horizon): void;

    /**
     * Get the current planning horizon.
     */
    public function getPlanningHorizon(): PlanningHorizon;

    /**
     * Configure planning zones (frozen, slushy, liquid).
     *
     * @param array{frozen: int, slushy: int, liquid: int} $zones Days for each zone
     */
    public function setZones(array $zones): void;

    /**
     * Get current zone configuration.
     *
     * @return array{frozen: int, slushy: int, liquid: int}
     */
    public function getZones(): array;
}
