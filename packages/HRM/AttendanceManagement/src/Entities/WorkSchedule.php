<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Entities;

use Nexus\AttendanceManagement\Contracts\WorkScheduleInterface;
use Nexus\AttendanceManagement\ValueObjects\ScheduleId;

/**
 * Work Schedule entity
 * Represents expected work hours for an employee
 */
final readonly class WorkSchedule implements WorkScheduleInterface
{
    public function __construct(
        private ScheduleId $id,
        private string $employeeId,
        private string $scheduleName,
        private \DateTimeImmutable $startTime,
        private \DateTimeImmutable $endTime,
        private \DateTimeImmutable $effectiveFrom,
        private ?\DateTimeImmutable $effectiveTo = null,
        private ?int $dayOfWeek = null,
        private float $expectedHours = 8.0
    ) {}

    public function getId(): ScheduleId
    {
        return $this->id;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function getScheduleName(): string
    {
        return $this->scheduleName;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function getEffectiveFrom(): \DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function getEffectiveTo(): ?\DateTimeImmutable
    {
        return $this->effectiveTo;
    }

    public function getExpectedHours(): float
    {
        return $this->expectedHours;
    }

    /**
     * Check if schedule is effective on a given date
     */
    public function isEffectiveOn(\DateTimeImmutable $date): bool
    {
        if ($date < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $date > $this->effectiveTo) {
            return false;
        }

        // Check day of week if specified
        if ($this->dayOfWeek !== null) {
            $dateDayOfWeek = (int) $date->format('N'); // 1 = Monday, 7 = Sunday
            return $dateDayOfWeek === $this->dayOfWeek;
        }

        return true;
    }

    /**
     * Calculate if check-in is late
     */
    public function isLateCheckIn(\DateTimeImmutable $checkInTime, int $graceMinutes = 15): bool
    {
        $scheduledStart = $this->startTime;
        $allowedTime = $scheduledStart->modify("+{$graceMinutes} minutes");
        
        return $checkInTime > $allowedTime;
    }

    /**
     * Calculate if check-out is early
     */
    public function isEarlyCheckOut(\DateTimeImmutable $checkOutTime, int $graceMinutes = 15): bool
    {
        $scheduledEnd = $this->endTime;
        $minimumTime = $scheduledEnd->modify("-{$graceMinutes} minutes");
        
        return $checkOutTime < $minimumTime;
    }
}
