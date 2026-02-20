<?php

declare(strict_types=1);

namespace Nexus\Attendance\Contracts;

/**
 * Query interface for attendance records (CQRS Read Model)
 * Read operations only - follows ISP
 */
interface AttendanceQueryInterface
{
    /**
     * Find attendance record by ID
     */
    public function findById(string $id): ?AttendanceRecordInterface;
    
    /**
     * Find attendance record for an employee on a specific date
     */
    public function findByEmployeeAndDate(string $employeeId, \DateTimeImmutable $date): ?AttendanceRecordInterface;
    
    /**
     * Find all attendance records for an employee in a date range
     * 
     * @return array<AttendanceRecordInterface>
     */
    public function findByEmployeeAndDateRange(
        string $employeeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;
    
    /**
     * Check if employee has checked in today
     */
    public function hasCheckedInToday(string $employeeId, \DateTimeImmutable $date): bool;
}

/**
 * Persistence interface for attendance records (CQRS Write Model)
 * Write operations only - follows ISP
 */
interface AttendancePersistInterface
{
    /**
     * Save attendance record (create or update)
     * 
     * @return string The attendance ID
     */
    public function save(AttendanceRecordInterface $attendance): string;
    
    /**
     * Delete attendance record by ID
     */
    public function delete(string $id): void;
}
