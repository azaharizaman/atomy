<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Disciplinary severity value object.
 */
enum DisciplinarySeverity: string
{
    case MINOR = 'minor';
    case MODERATE = 'moderate';
    case SERIOUS = 'serious';
    case CRITICAL = 'critical';
    
    public function label(): string
    {
        return match($this) {
            self::MINOR => 'Minor',
            self::MODERATE => 'Moderate',
            self::SERIOUS => 'Serious',
            self::CRITICAL => 'Critical',
        };
    }
    
    public function level(): int
    {
        return match($this) {
            self::MINOR => 1,
            self::MODERATE => 2,
            self::SERIOUS => 3,
            self::CRITICAL => 4,
        };
    }
}
