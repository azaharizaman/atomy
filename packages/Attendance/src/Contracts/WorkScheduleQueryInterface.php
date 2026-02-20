<?php

declare(strict_types=1);

namespace Nexus\Attendance\Contracts;

/**
 * Query interface for work schedules (CQRS Read Model)
 * 
 * Provides read-only operations for retrieving work schedules.
 * Follows CQRS principle by separating queries from commands.
 */
interface WorkScheduleQueryInterface
{
    /**
     * Find work schedule by ID
     * 
     * @param string $id Schedule ID
     * @return WorkScheduleInterface|null
     */
    public function findById(string $id): ?WorkScheduleInterface;

    /**
     * Find all work schedules for employee
     * 
     * @param string $employeeId Employee ID
     * @return array<WorkScheduleInterface>
     */
    public function findByEmployeeId(string $employeeId): array;

    /**
     * Find effective work schedule for employee on specific date
     * 
     * @param string $employeeId Employee ID
     * @param \DateTimeImmutable $date Date to check
     * @return WorkScheduleInterface|null
     */
    public function findEffectiveSchedule(string $employeeId, \DateTimeImmutable $date): ?WorkScheduleInterface;
}
