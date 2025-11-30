<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\WorkCenterNotFoundException;
use Nexus\Manufacturing\ValueObjects\CapacityPeriod;

/**
 * Manager interface for Work Center operations.
 *
 * Provides business logic layer for work center management.
 */
interface WorkCenterManagerInterface
{
    /**
     * Create a new work center.
     */
    public function create(
        string $code,
        string $name,
        float $capacityHoursPerDay,
        float $efficiency = 1.0,
        float $utilization = 1.0,
        float $laborRate = 0.0,
        float $machineRate = 0.0,
        float $overheadRate = 0.0,
        ?string $costCenterId = null
    ): WorkCenterInterface;

    /**
     * Get a work center by ID.
     *
     * @throws WorkCenterNotFoundException If work center not found
     */
    public function getById(string $id): WorkCenterInterface;

    /**
     * Get a work center by code.
     *
     * @throws WorkCenterNotFoundException If work center not found
     */
    public function getByCode(string $code): WorkCenterInterface;

    /**
     * Update work center details.
     *
     * @throws WorkCenterNotFoundException If work center not found
     */
    public function update(string $workCenterId, array $data): void;

    /**
     * Deactivate a work center.
     *
     * @throws WorkCenterNotFoundException If work center not found
     */
    public function deactivate(string $workCenterId): void;

    /**
     * Set calendar for a work center.
     *
     * @param array<CapacityPeriod> $periods Working time periods
     */
    public function setCalendar(
        string $workCenterId,
        \DateTimeImmutable $date,
        array $periods,
        bool $isWorkingDay = true
    ): void;

    /**
     * Copy calendar from one period to another.
     *
     * Useful for setting up recurring schedules.
     */
    public function copyCalendar(
        string $workCenterId,
        \DateTimeImmutable $sourceStart,
        \DateTimeImmutable $sourceEnd,
        \DateTimeImmutable $targetStart
    ): void;

    /**
     * Get available capacity in hours for a date range.
     *
     * @return array<string, float> Date => hours map
     */
    public function getAvailableCapacity(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Get loaded capacity (committed work) for a date range.
     *
     * @return array<string, float> Date => hours map
     */
    public function getLoadedCapacity(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Get all active work centers.
     *
     * @return array<WorkCenterInterface>
     */
    public function findActive(): array;

    /**
     * Get available hours for a work center within a period.
     *
     * @param string $workCenterId Work center ID
     * @param \DateTimeImmutable $startDate Period start
     * @param \DateTimeImmutable $endDate Period end
     * @return float Available hours
     */
    public function getAvailableHoursForPeriod(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): float;

    /**
     * Get capacity utilization percentage for a date range.
     *
     * @return array<string, float> Date => percentage map
     */
    public function getUtilization(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Calculate cost for an operation at this work center.
     *
     * @param float $setupHours Setup time in hours
     * @param float $runHours Run time in hours
     * @return array{labor: float, machine: float, overhead: float, total: float}
     */
    public function calculateCost(
        string $workCenterId,
        float $setupHours,
        float $runHours
    ): array;

    /**
     * Find alternative work centers capable of performing operation.
     *
     * @return array<WorkCenterInterface>
     */
    public function findAlternatives(string $workCenterId): array;
}
