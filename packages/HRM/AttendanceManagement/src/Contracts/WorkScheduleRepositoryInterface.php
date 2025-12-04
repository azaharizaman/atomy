<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Contracts;

/**
 * Query interface for work schedules (CQRS Read Model)
 * Read operations only - follows ISP
 */
interface WorkScheduleQueryInterface
{
    /**
     * Find work schedule by ID
     */
    public function findById(string $id): ?WorkScheduleInterface;
    
    /**
     * Find all work schedules for an employee
     * 
     * @return array<WorkScheduleInterface>
     */
    public function findByEmployeeId(string $employeeId): array;
    
    /**
     * Find effective schedule for an employee on a specific date
     */
    public function findEffectiveSchedule(string $employeeId, \DateTimeImmutable $date): ?WorkScheduleInterface;
}

/**
 * Persistence interface for work schedules (CQRS Write Model)
 * Write operations only - follows ISP
 */
interface WorkSchedulePersistInterface
{
    /**
     * Save work schedule (create or update)
     * 
     * @return string The schedule ID
     */
    public function save(WorkScheduleInterface $schedule): string;
    
    /**
     * Delete work schedule by ID
     */
    public function delete(string $id): void;
}
