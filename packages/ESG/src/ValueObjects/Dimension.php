<?php

declare(strict_types=1);

namespace Nexus\ESG\ValueObjects;

/**
 * Enum representing the core dimensions of ESG.
 */
enum Dimension: string
{
    case ENVIRONMENTAL = 'environmental';
    case SOCIAL = 'social';
    case GOVERNANCE = 'governance';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ENVIRONMENTAL => 'Environmental',
            self::SOCIAL => 'Social',
            self::GOVERNANCE => 'Governance',
        };
    }
}
