<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Attendance status value object.
 */
enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case HALF_DAY = 'half_day';
    case ON_LEAVE = 'on_leave';
    case HOLIDAY = 'holiday';
    case WEEKEND = 'weekend';
    
    public function label(): string
    {
        return match($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::LATE => 'Late',
            self::HALF_DAY => 'Half Day',
            self::ON_LEAVE => 'On Leave',
            self::HOLIDAY => 'Holiday',
            self::WEEKEND => 'Weekend',
        };
    }
    
    public function isWorking(): bool
    {
        return match($this) {
            self::PRESENT, self::LATE, self::HALF_DAY => true,
            self::ABSENT, self::ON_LEAVE, self::HOLIDAY, self::WEEKEND => false,
        };
    }
}
