<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\ValueObjects;

/**
 * Value object for Attendance unique identifier
 */
final readonly class AttendanceId
{
    public function __construct(
        public string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Attendance ID cannot be empty');
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
