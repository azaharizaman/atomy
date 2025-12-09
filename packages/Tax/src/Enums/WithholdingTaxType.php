<?php

declare(strict_types=1);

namespace Nexus\Tax\Enums;

/**
 * Withholding Tax Type: Level/authority of withholding
 * 
 * Represents which tax authority the withholding is for.
 * Payments may have multiple WHT types (federal + state).
 */
enum WithholdingTaxType: string
{
    case FEDERAL = 'federal';
    case STATE = 'state';
    case LOCAL = 'local';
    case TREATY = 'treaty';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::FEDERAL => 'Federal Withholding',
            self::STATE => 'State Withholding',
            self::LOCAL => 'Local/Municipal Withholding',
            self::TREATY => 'Treaty-Reduced Withholding',
        };
    }

    /**
     * Get default priority (for ordering multiple WHT)
     */
    public function priority(): int
    {
        return match ($this) {
            self::FEDERAL => 1,
            self::STATE => 2,
            self::LOCAL => 3,
            self::TREATY => 1, // Treaty replaces federal
        };
    }
}
