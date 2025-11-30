<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Contract type value object.
 */
enum ContractType: string
{
    case PERMANENT = 'permanent';
    case FIXED_TERM = 'fixed_term';
    case PROBATIONARY = 'probationary';
    case CONSULTANCY = 'consultancy';
    case INTERNSHIP = 'internship';
    
    public function label(): string
    {
        return match($this) {
            self::PERMANENT => 'Permanent',
            self::FIXED_TERM => 'Fixed Term',
            self::PROBATIONARY => 'Probationary',
            self::CONSULTANCY => 'Consultancy',
            self::INTERNSHIP => 'Internship',
        };
    }
    
    public function requiresEndDate(): bool
    {
        return match($this) {
            self::PERMANENT => false,
            self::FIXED_TERM, self::PROBATIONARY, self::CONSULTANCY, self::INTERNSHIP => true,
        };
    }
}
