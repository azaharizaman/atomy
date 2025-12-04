<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Services;

use Nexus\AttendanceManagement\Contracts\WorkScheduleQueryInterface;
use Nexus\AttendanceManagement\Contracts\WorkScheduleInterface;
use Nexus\AttendanceManagement\Exceptions\WorkScheduleNotFoundException;

/**
 * Resolves appropriate work schedule for an employee on a given date
 */
final readonly class WorkScheduleResolver
{
    public function __construct(
        private WorkScheduleQueryInterface $scheduleQuery
    ) {}

    /**
     * Find the effective work schedule for an employee on a specific date
     * 
     * @throws WorkScheduleNotFoundException
     */
    public function resolveSchedule(string $employeeId, \DateTimeImmutable $date): WorkScheduleInterface
    {
        $schedule = $this->scheduleQuery->findEffectiveSchedule($employeeId, $date);
        
        if ($schedule === null) {
            throw WorkScheduleNotFoundException::forEmployee($employeeId, $date);
        }

        return $schedule;
    }

    /**
     * Try to find schedule without throwing exception
     */
    public function tryResolveSchedule(string $employeeId, \DateTimeImmutable $date): ?WorkScheduleInterface
    {
        return $this->scheduleQuery->findEffectiveSchedule($employeeId, $date);
    }

    /**
     * Check if employee has a schedule for the given date
     */
    public function hasSchedule(string $employeeId, \DateTimeImmutable $date): bool
    {
        return $this->scheduleQuery->findEffectiveSchedule($employeeId, $date) !== null;
    }
}
