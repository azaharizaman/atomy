<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Training status value object.
 */
enum TrainingStatus: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::PLANNED => 'Planned',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    public function isAcceptingEnrollments(): bool
    {
        return match($this) {
            self::PLANNED, self::ACTIVE => true,
            self::COMPLETED, self::CANCELLED => false,
        };
    }
}
