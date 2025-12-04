<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Services;

use Nexus\AttendanceManagement\Contracts\AttendanceManagerInterface;
use Nexus\AttendanceManagement\Contracts\AttendanceQueryInterface;
use Nexus\AttendanceManagement\Contracts\AttendancePersistInterface;
use Nexus\AttendanceManagement\Contracts\AttendanceRecordInterface;
use Nexus\AttendanceManagement\Contracts\WorkScheduleQueryInterface;
use Nexus\AttendanceManagement\Entities\AttendanceRecord;
use Nexus\AttendanceManagement\Enums\AttendanceStatus;
use Nexus\AttendanceManagement\Exceptions\InvalidCheckTimeException;
use Nexus\AttendanceManagement\ValueObjects\AttendanceId;
use Nexus\AttendanceManagement\ValueObjects\WorkHours;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Core attendance management service
 * Handles check-in/check-out operations
 */
final readonly class AttendanceManager implements AttendanceManagerInterface
{
    public function __construct(
        private AttendanceQueryInterface $attendanceQuery,
        private AttendancePersistInterface $attendancePersist,
        private WorkScheduleQueryInterface $scheduleQuery,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function checkIn(
        string $employeeId,
        \DateTimeImmutable $timestamp,
        ?string $locationId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): AttendanceId {
        $date = new \DateTimeImmutable($timestamp->format('Y-m-d'));
        
        // Check if already checked in
        $existing = $this->attendanceQuery->findByEmployeeAndDate($employeeId, $date);
        if ($existing && $existing->isCheckedIn()) {
            throw InvalidCheckTimeException::alreadyCheckedIn();
        }

        // Get work schedule
        $schedule = $this->scheduleQuery->findEffectiveSchedule($employeeId, $date);
        
        // Create or update attendance record
        if ($existing) {
            $attendance = $existing->withCheckIn($timestamp, $locationId, $latitude, $longitude);
        } else {
            $attendanceId = new AttendanceId($this->generateId());
            $attendance = new AttendanceRecord(
                $attendanceId,
                $employeeId,
                $date,
                $timestamp,
                null,
                AttendanceStatus::PRESENT,
                null,
                $schedule?->getId()->toString(),
                $locationId,
                $latitude,
                $longitude
            );
        }

        $this->attendancePersist->save($attendance);
        
        $this->logger->info('Employee checked in', [
            'employee_id' => $employeeId,
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'location_id' => $locationId,
        ]);

        return $attendance->getId();
    }

    public function checkOut(
        string $employeeId,
        \DateTimeImmutable $timestamp
    ): AttendanceId {
        $date = new \DateTimeImmutable($timestamp->format('Y-m-d'));
        
        // Get existing attendance record
        $existing = $this->attendanceQuery->findByEmployeeAndDate($employeeId, $date);
        if (!$existing) {
            throw InvalidCheckTimeException::notCheckedIn();
        }

        if (!$existing->isCheckedIn()) {
            throw InvalidCheckTimeException::notCheckedIn();
        }

        if ($existing->getCheckInTime() > $timestamp) {
            throw InvalidCheckTimeException::checkOutBeforeCheckIn();
        }

        // Calculate work hours
        $workHours = WorkHours::fromDuration(
            $existing->getCheckInTime(),
            $timestamp
        );

        $attendance = $existing->withCheckOut($timestamp, $workHours);
        $this->attendancePersist->save($attendance);

        $this->logger->info('Employee checked out', [
            'employee_id' => $employeeId,
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'work_hours' => $workHours->getTotalHours(),
        ]);

        return $attendance->getId();
    }

    public function getAttendanceForDate(
        string $employeeId,
        \DateTimeImmutable $date
    ): ?AttendanceRecordInterface {
        return $this->attendanceQuery->findByEmployeeAndDate($employeeId, $date);
    }

    public function isCheckedIn(string $employeeId, \DateTimeImmutable $date): bool
    {
        return $this->attendanceQuery->hasCheckedInToday($employeeId, $date);
    }

    /**
     * Generate a unique attendance ID
     */
    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'ATT-' . $uuid;
    }
}
