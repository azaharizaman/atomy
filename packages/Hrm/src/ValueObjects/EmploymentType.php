<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Employment type value object.
 */
enum EmploymentType: string
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';
    case TEMPORARY = 'temporary';
    case INTERN = 'intern';
    case CONSULTANT = 'consultant';
    
    public function label(): string
    {
        return match($this) {
            self::FULL_TIME => 'Full Time',
            self::PART_TIME => 'Part Time',
            self::CONTRACT => 'Contract',
            self::TEMPORARY => 'Temporary',
            self::INTERN => 'Intern',
            self::CONSULTANT => 'Consultant',
        };
    }
}
