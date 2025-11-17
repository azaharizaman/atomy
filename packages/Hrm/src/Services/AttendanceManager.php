<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use DateTimeInterface;
use Nexus\Hrm\Contracts\AttendanceInterface;
use Nexus\Hrm\Contracts\AttendanceRepositoryInterface;
use Nexus\Hrm\Exceptions\AttendanceNotFoundException;
use Nexus\Hrm\Exceptions\AttendanceValidationException;
use Nexus\Hrm\ValueObjects\AttendanceStatus;

/**
 * Service for managing employee attendance tracking.
 */
readonly class AttendanceManager
{
    public function __construct(
        private AttendanceRepositoryInterface $attendanceRepository,
    ) {
    }
    
    /**
     * Clock in an employee.
     *
     * @param string $employeeId Employee ULID
     * @param array<string, mixed> $data Clock in data (location, latitude, longitude, etc.)
     * @return AttendanceInterface
     * @throws \Nexus\Hrm\Exceptions\AttendanceDuplicateException
     * @throws AttendanceValidationException
     */
    public function clockIn(string $employeeId, array $data = []): AttendanceInterface
    {
        $today = new \DateTime('today');
        
        // Check if already clocked in today
        $existing = $this->attendanceRepository->findByEmployeeAndDate($employeeId, $today);
        if ($existing && $existing->getClockInTime()) {
            throw AttendanceValidationException::alreadyClockedIn($employeeId);
        }
        
        $attendanceData = array_merge($data, [
            'employee_id' => $employeeId,
            'date' => $today->format('Y-m-d'),
            'clock_in_time' => now(),
            'status' => AttendanceStatus::PRESENT->value,
        ]);
        
        // Extract location data if provided
        if (isset($data['location'])) {
            $attendanceData['clock_in_location'] = $data['location'];
        }
        if (isset($data['latitude'])) {
            $attendanceData['clock_in_latitude'] = $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $attendanceData['clock_in_longitude'] = $data['longitude'];
        }
        
        return $this->attendanceRepository->create($attendanceData);
    }
    
    /**
     * Clock out an employee.
     *
     * @param string $attendanceId Attendance ULID
     * @param array<string, mixed> $data Clock out data (location, latitude, longitude, etc.)
     * @return AttendanceInterface
     * @throws AttendanceNotFoundException
     * @throws AttendanceValidationException
     */
    public function clockOut(string $attendanceId, array $data = []): AttendanceInterface
    {
        $attendance = $this->getAttendanceById($attendanceId);
        
        if (!$attendance->getClockInTime()) {
            throw AttendanceValidationException::notClockedIn($attendance->getEmployeeId());
        }
        
        if ($attendance->getClockOutTime()) {
            throw new AttendanceValidationException("Employee has already clocked out today.");
        }
        
        $updateData = array_merge($data, [
            'clock_out_time' => now(),
        ]);
        
        // Extract location data if provided
        if (isset($data['location'])) {
            $updateData['clock_out_location'] = $data['location'];
        }
        if (isset($data['latitude'])) {
            $updateData['clock_out_latitude'] = $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $updateData['clock_out_longitude'] = $data['longitude'];
        }
        
        // Calculate total hours
        $clockInTime = $attendance->getClockInTime();
        $clockOutTime = new \DateTime($updateData['clock_out_time']);
        $diff = $clockInTime->diff($clockOutTime);
        $totalMinutes = ($diff->h * 60) + $diff->i - $attendance->getBreakMinutes();
        $updateData['total_hours'] = round($totalMinutes / 60, 2);
        
        return $this->attendanceRepository->update($attendanceId, $updateData);
    }
    
    /**
     * Record break time for an attendance.
     *
     * @param string $attendanceId Attendance ULID
     * @param int $breakMinutes Break duration in minutes
     * @return AttendanceInterface
     * @throws AttendanceNotFoundException
     */
    public function recordBreak(string $attendanceId, int $breakMinutes): AttendanceInterface
    {
        $attendance = $this->getAttendanceById($attendanceId);
        
        return $this->attendanceRepository->update($attendanceId, [
            'break_minutes' => $breakMinutes,
        ]);
    }
    
    /**
     * Record overtime hours.
     *
     * @param string $attendanceId Attendance ULID
     * @param float $overtimeHours Overtime hours
     * @return AttendanceInterface
     * @throws AttendanceNotFoundException
     */
    public function recordOvertime(string $attendanceId, float $overtimeHours): AttendanceInterface
    {
        $attendance = $this->getAttendanceById($attendanceId);
        
        return $this->attendanceRepository->update($attendanceId, [
            'overtime_hours' => $overtimeHours,
        ]);
    }
    
    /**
     * Mark employee as absent.
     *
     * @param string $employeeId Employee ULID
     * @param string $date Date (Y-m-d)
     * @param string|null $remarks Optional remarks
     * @return AttendanceInterface
     * @throws \Nexus\Hrm\Exceptions\AttendanceDuplicateException
     */
    public function markAbsent(string $employeeId, string $date, ?string $remarks = null): AttendanceInterface
    {
        return $this->attendanceRepository->create([
            'employee_id' => $employeeId,
            'date' => $date,
            'status' => AttendanceStatus::ABSENT->value,
            'remarks' => $remarks,
        ]);
    }
    
    /**
     * Get attendance by ID.
     *
     * @param string $id Attendance ULID
     * @return AttendanceInterface
     * @throws AttendanceNotFoundException
     */
    public function getAttendanceById(string $id): AttendanceInterface
    {
        $attendance = $this->attendanceRepository->findById($id);
        
        if (!$attendance) {
            throw AttendanceNotFoundException::forId($id);
        }
        
        return $attendance;
    }
    
    /**
     * Get attendance for employee on specific date.
     *
     * @param string $employeeId Employee ULID
     * @param DateTimeInterface $date Target date
     * @return AttendanceInterface|null
     */
    public function getAttendanceForDate(string $employeeId, DateTimeInterface $date): ?AttendanceInterface
    {
        return $this->attendanceRepository->findByEmployeeAndDate($employeeId, $date);
    }
    
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
    public function getMonthlyAttendanceSummary(string $employeeId, int $year, int $month): array
    {
        return $this->attendanceRepository->getMonthlySummary($employeeId, $year, $month);
    }
    
    /**
     * Get attendance records for employee in date range.
     *
     * @param string $employeeId Employee ULID
     * @param DateTimeInterface $startDate Start date
     * @param DateTimeInterface $endDate End date
     * @return array<AttendanceInterface>
     */
    public function getEmployeeAttendance(
        string $employeeId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): array {
        return $this->attendanceRepository->getEmployeeAttendance($employeeId, $startDate, $endDate);
    }
}
