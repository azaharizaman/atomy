<?php

declare(strict_types=1);

namespace Nexus\Localization\Enums;

/**
 * Text direction for locale-specific rendering.
 *
 * Determines the reading/writing direction for a locale.
 */
enum TextDirection: string
{
    case LTR = 'ltr';
    case RTL = 'rtl';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LTR => 'Left to Right',
            self::RTL => 'Right to Left',
        };
    }

    /**
     * Check if direction is left-to-right.
     */
    public function isLeftToRight(): bool
    {
        return $this === self::LTR;
    }

    /**
     * Check if direction is right-to-left.
     */
    public function isRightToLeft(): bool
    {
        return $this === self::RTL;
    }
}
