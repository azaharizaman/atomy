<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DataProviders;

use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\WorkScheduleQueryInterface;
use Nexus\EmployeeProfile\Contracts\EmployeeRepositoryInterface;
use Nexus\HumanResourceOperations\DTOs\AttendanceContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Aggregates attendance-related data from multiple sources.
 */
final readonly class AttendanceDataProvider
{
    public function __construct(
        private ?AttendanceQueryInterface $attendanceQuery = null,
        private ?WorkScheduleQueryInterface $scheduleQuery = null,
        private ?EmployeeRepositoryInterface $employeeRepository = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function getAttendanceContext(
        string $employeeId,
        \DateTimeImmutable $timestamp,
        string $type,
        ?string $locationId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): AttendanceContext {
        $this->logger->info('Building attendance context', [
            'employee_id' => $employeeId,
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'type' => $type,
        ]);

        $schedule = $this->getEmployeeSchedule($employeeId, $timestamp);
        $recentAttendance = $this->getRecentAttendance($employeeId, $timestamp);
        $workPattern = $this->getEmployeeWorkPattern($employeeId);

        return new AttendanceContext(
            employeeId: $employeeId,
            timestamp: $timestamp,
            type: $type,
            scheduleId: $schedule['id'] ?? null,
            scheduledStart: isset($schedule['start']) ? new \DateTimeImmutable($schedule['start']) : null,
            scheduledEnd: isset($schedule['end']) ? new \DateTimeImmutable($schedule['end']) : null,
            locationId: $locationId,
            latitude: $latitude,
            longitude: $longitude,
            recentAttendance: $recentAttendance,
            employeeWorkPattern: $workPattern
        );
    }

    private function getEmployeeSchedule(string $employeeId, \DateTimeImmutable $date): ?array
    {
        if ($this->scheduleQuery === null) {
            return null;
        }

        $schedule = $this->scheduleQuery->findEffectiveSchedule($employeeId, $date);
        if ($schedule === null) {
            return null;
        }

        return [
            'id' => $schedule->getId()->toString(),
            'start' => $schedule->getStartTime()->format('Y-m-d H:i:s'),
            'end' => $schedule->getEndTime()->format('Y-m-d H:i:s'),
            'expected_hours' => $schedule->getExpectedHours(),
        ];
    }

    private function getRecentAttendance(string $employeeId, \DateTimeImmutable $date): array
    {
        if ($this->attendanceQuery === null) {
            return [];
        }

        $start = $date->modify('-7 days');
        $records = $this->attendanceQuery->findByEmployeeAndDateRange($employeeId, $start, $date);

        return array_map(static function ($record): array {
            return [
                'id' => $record->getId()->toString(),
                'date' => $record->getDate()->format('Y-m-d'),
                'check_in' => $record->getCheckInTime()?->format('Y-m-d H:i:s'),
                'check_out' => $record->getCheckOutTime()?->format('Y-m-d H:i:s'),
                'location_id' => $record->getLocationId(),
            ];
        }, $records);
    }

    private function getEmployeeWorkPattern(string $employeeId): ?array
    {
        if ($this->employeeRepository === null) {
            return null;
        }

        $employee = $this->employeeRepository->findById($employeeId);
        if ($employee === null) {
            return null;
        }

        return [
            'id' => $employeeId,
            'work_pattern' => $this->readValue($employee, ['getWorkPattern', 'workPattern']) ?? 'default',
            'shift' => $this->readValue($employee, ['getShiftType', 'shiftType']) ?? 'day',
        ];
    }

    /** @param array<string> $candidates */
    private function readValue(object $source, array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            if (method_exists($source, $candidate)) {
                return $source->{$candidate}();
            }

            if (property_exists($source, $candidate)) {
                return $source->{$candidate};
            }
        }

        return null;
    }
}
