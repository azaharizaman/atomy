<?php

declare(strict_types=1);

namespace Nexus\Attendance\ValueObjects;

/**
 * Value object for work hours calculation
 */
final readonly class WorkHours
{
    public function __construct(
        public float $regularHours,
        public float $overtimeHours = 0.0,
        public float $totalHours = 0.0
    ) {
        if ($regularHours < 0) {
            throw new \InvalidArgumentException('Regular hours cannot be negative');
        }
        if ($overtimeHours < 0) {
            throw new \InvalidArgumentException('Overtime hours cannot be negative');
        }
    }

    public static function fromDuration(
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
        float $standardHoursPerDay = 8.0
    ): self {
        $interval = $checkIn->diff($checkOut);
        $totalHours = ($interval->h + ($interval->i / 60));
        
        $regularHours = min($totalHours, $standardHoursPerDay);
        $overtimeHours = max(0, $totalHours - $standardHoursPerDay);
        
        return new self($regularHours, $overtimeHours, $totalHours);
    }

    public function getTotalHours(): float
    {
        return $this->regularHours + $this->overtimeHours;
    }
}
