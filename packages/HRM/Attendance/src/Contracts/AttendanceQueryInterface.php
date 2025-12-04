<?php

declare(strict_types=1);

namespace Nexus\Attendance\Contracts;

/**
 * Query interface for attendance records (CQRS Read Model)
 * 
 * Provides read-only operations for retrieving attendance records.
 * Follows CQRS principle by separating queries from commands.
 */
interface AttendanceQueryInterface
{
    /**
     * Find attendance record by ID
     * 
     * @param string $id Attendance record ID
     * @return AttendanceRecordInterface|null
     */
    public function findById(string $id): ?AttendanceRecordInterface;

    /**
     * Find attendance record for specific employee and date
     * 
     * @param string $employeeId Employee ID
     * @param \DateTimeImmutable $date Date to check
     * @return AttendanceRecordInterface|null
     */
    public function findByEmployeeAndDate(string $employeeId, \DateTimeImmutable $date): ?AttendanceRecordInterface;

    /**
     * Find all attendance records for employee within date range
     * 
     * @param string $employeeId Employee ID
     * @param \DateTimeImmutable $startDate Start date (inclusive)
     * @param \DateTimeImmutable $endDate End date (inclusive)
     * @return array<AttendanceRecordInterface>
     */
    public function findByEmployeeAndDateRange(
        string $employeeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Check if employee has checked in today
     * 
     * @param string $employeeId Employee ID
     * @param \DateTimeImmutable $date Date to check
     * @return bool
     */
    public function hasCheckedInToday(string $employeeId, \DateTimeImmutable $date): bool;
}
