<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Employee lifecycle status value object.
 */
enum EmployeeStatus: string
{
    case PROBATIONARY = 'probationary';
    case CONFIRMED = 'confirmed';
    case RESIGNED = 'resigned';
    case TERMINATED = 'terminated';
    case RETIRED = 'retired';
    case SUSPENDED = 'suspended';
    
    public function isActive(): bool
    {
        return match($this) {
            self::PROBATIONARY, self::CONFIRMED => true,
            self::RESIGNED, self::TERMINATED, self::RETIRED, self::SUSPENDED => false,
        };
    }
    
    public function label(): string
    {
        return match($this) {
            self::PROBATIONARY => 'Probationary',
            self::CONFIRMED => 'Confirmed',
            self::RESIGNED => 'Resigned',
            self::TERMINATED => 'Terminated',
            self::RETIRED => 'Retired',
            self::SUSPENDED => 'Suspended',
        };
    }
}
