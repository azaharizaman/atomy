<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\WorkCenterNotFoundException;

/**
 * Repository interface for Work Center persistence.
 *
 * Consumers must implement this interface to provide Work Center storage.
 */
interface WorkCenterRepositoryInterface
{
    /**
     * Find a work center by ID.
     *
     * @throws WorkCenterNotFoundException If work center not found
     */
    public function findById(string $id): WorkCenterInterface;

    /**
     * Find a work center by ID or return null.
     */
    public function findByIdOrNull(string $id): ?WorkCenterInterface;

    /**
     * Find a work center by code.
     *
     * @throws WorkCenterNotFoundException If work center not found
     */
    public function findByCode(string $code): WorkCenterInterface;

    /**
     * Find all active work centers.
     *
     * @return array<WorkCenterInterface>
     */
    public function findAllActive(): array;

    /**
     * Find active work centers (alias for findAllActive).
     *
     * @return array<WorkCenterInterface>
     */
    public function findActive(): array;

    /**
     * Find work centers by cost center.
     *
     * @return array<WorkCenterInterface>
     */
    public function findByCostCenter(string $costCenterId): array;

    /**
     * Save a work center (create or update).
     */
    public function save(WorkCenterInterface $workCenter): void;

    /**
     * Delete a work center by ID.
     */
    public function delete(string $id): void;

    /**
     * Get calendar entries for a work center within date range.
     *
     * @return array<WorkCenterCalendarInterface>
     */
    public function getCalendar(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Save calendar entries.
     *
     * @param array<WorkCenterCalendarInterface> $entries
     */
    public function saveCalendar(array $entries): void;

    /**
     * Get available capacity in hours for a work center on a date.
     */
    public function getAvailableCapacity(string $workCenterId, \DateTimeImmutable $date): float;

    /**
     * Get total available capacity in hours for date range.
     */
    public function getTotalCapacity(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): float;

    /**
     * Get available hours for a period.
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
}
