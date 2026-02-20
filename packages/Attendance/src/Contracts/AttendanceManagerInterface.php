<?php

declare(strict_types=1);

namespace Nexus\Attendance\Contracts;

use Nexus\Attendance\Enums\CheckType;
use Nexus\Attendance\ValueObjects\AttendanceId;

/**
 * Main attendance management service interface
 */
interface AttendanceManagerInterface
{
    /**
     * Record check-in for an employee
     * 
     * @throws \Nexus\Attendance\Exceptions\InvalidCheckTimeException
     */
    public function checkIn(
        string $employeeId,
        \DateTimeImmutable $timestamp,
        ?string $locationId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): AttendanceId;

    /**
     * Record check-out for an employee
     * 
     * @throws \Nexus\Attendance\Exceptions\InvalidCheckTimeException
     * @throws \Nexus\Attendance\Exceptions\AttendanceNotFoundException
     */
    public function checkOut(
        string $employeeId,
        \DateTimeImmutable $timestamp
    ): AttendanceId;

    /**
     * Get attendance record for employee on specific date
     */
    public function getAttendanceForDate(
        string $employeeId,
        \DateTimeImmutable $date
    ): ?AttendanceRecordInterface;

    /**
     * Check if employee is currently checked in
     */
    public function isCheckedIn(string $employeeId, \DateTimeImmutable $date): bool;
}
