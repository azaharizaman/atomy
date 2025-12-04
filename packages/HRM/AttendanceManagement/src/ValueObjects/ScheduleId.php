<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\ValueObjects;

/**
 * Value object for Work Schedule unique identifier
 */
final readonly class ScheduleId
{
    public function __construct(
        public string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Schedule ID cannot be empty');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
