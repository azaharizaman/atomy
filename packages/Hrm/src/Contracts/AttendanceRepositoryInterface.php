<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Repository contract for attendance record persistence operations.
 */
interface AttendanceRepositoryInterface
{
    /**
     * Find attendance record by ID.
     *
     * @param string $id Attendance ULID
     * @return AttendanceInterface|null
     */
    public function findById(string $id): ?AttendanceInterface;
    
    /**
     * Find attendance record for employee on specific date.
     *
     * @param string $employeeId Employee ULID
     * @param DateTimeInterface $date Target date
     * @return AttendanceInterface|null
     */
    public function findByEmployeeAndDate(string $employeeId, DateTimeInterface $date): ?AttendanceInterface;
    
    /**
     * Get attendance records for employee in date range.
     *
     * @param string $employeeId Employee ULID
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @return array<AttendanceInterface>
     */
    public function getEmployeeAttendance(
        string $employeeId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): array;
    
    /**
     * Get attendance records for tenant in date range with filters.
     *
     * @param string $tenantId Tenant ULID
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @param array<string, mixed> $filters
     * @return array<AttendanceInterface>
     */
    public function getAttendanceInDateRange(
        string $tenantId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $filters = []
    ): array;
    
    /**
     * Get monthly attendance summary for employee.
     *
     * @param string $employeeId Employee ULID
     * @param int $year Calendar year
     * @param int $month Month (1-12)
     * @return array{
     *     total_working_days: int,
     *     present_days: int,
     *     absent_days: int,
     *     late_days: int,
     *     total_hours: float,
     *     overtime_hours: float
     * }
     */
    public function getMonthlySummary(string $employeeId, int $year, int $month): array;
    
    /**
     * Create an attendance record.
     *
     * @param array<string, mixed> $data
     * @return AttendanceInterface
     * @throws \Nexus\Hrm\Exceptions\AttendanceValidationException
     * @throws \Nexus\Hrm\Exceptions\AttendanceDuplicateException
     */
    public function create(array $data): AttendanceInterface;
    
    /**
     * Update an attendance record.
     *
     * @param string $id Attendance ULID
     * @param array<string, mixed> $data
     * @return AttendanceInterface
     * @throws \Nexus\Hrm\Exceptions\AttendanceNotFoundException
     * @throws \Nexus\Hrm\Exceptions\AttendanceValidationException
     */
    public function update(string $id, array $data): AttendanceInterface;
    
    /**
     * Delete an attendance record.
     *
     * @param string $id Attendance ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\AttendanceNotFoundException
     */
    public function delete(string $id): bool;
}
