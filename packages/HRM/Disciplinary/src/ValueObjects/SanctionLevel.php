<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\ValueObjects;

enum SanctionLevel: string
{
    case VERBAL_WARNING = 'verbal_warning';
    case WRITTEN_WARNING = 'written_warning';
    case FINAL_WARNING = 'final_warning';
    case SUSPENSION = 'suspension';
    case DEMOTION = 'demotion';
    case TERMINATION = 'termination';
    case NONE = 'none';
    
    public function getSeverity(): int
    {
        return match ($this) {
            self::NONE => 0,
            self::VERBAL_WARNING => 1,
            self::WRITTEN_WARNING => 2,
            self::FINAL_WARNING => 3,
            self::SUSPENSION => 4,
            self::DEMOTION => 5,
            self::TERMINATION => 6,
        };
    }
}
