<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Training enrollment status value object.
 */
enum EnrollmentStatus: string
{
    case ENROLLED = 'enrolled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case PASSED = 'passed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::ENROLLED => 'Enrolled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::PASSED => 'Passed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    public function isActive(): bool
    {
        return match($this) {
            self::ENROLLED, self::IN_PROGRESS => true,
            self::COMPLETED, self::PASSED, self::FAILED, self::CANCELLED => false,
        };
    }
}
