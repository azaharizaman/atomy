<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Disciplinary case status value object.
 */
enum DisciplinaryStatus: string
{
    case REPORTED = 'reported';
    case UNDER_INVESTIGATION = 'under_investigation';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case APPEALED = 'appealed';
    
    public function label(): string
    {
        return match($this) {
            self::REPORTED => 'Reported',
            self::UNDER_INVESTIGATION => 'Under Investigation',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
            self::APPEALED => 'Appealed',
        };
    }
    
    public function isOpen(): bool
    {
        return match($this) {
            self::REPORTED, self::UNDER_INVESTIGATION, self::APPEALED => true,
            self::RESOLVED, self::CLOSED => false,
        };
    }
}
