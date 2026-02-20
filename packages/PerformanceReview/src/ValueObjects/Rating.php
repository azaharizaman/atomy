<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\ValueObjects;

enum Rating: string
{
    case OUTSTANDING = 'outstanding';
    case EXCEEDS = 'exceeds';
    case MEETS = 'meets';
    case NEEDS_IMPROVEMENT = 'needs_improvement';
    case UNSATISFACTORY = 'unsatisfactory';
    
    public function getNumericValue(): float
    {
        return match ($this) {
            self::OUTSTANDING => 5.0,
            self::EXCEEDS => 4.0,
            self::MEETS => 3.0,
            self::NEEDS_IMPROVEMENT => 2.0,
            self::UNSATISFACTORY => 1.0,
        };
    }
}
