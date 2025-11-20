<?php

declare(strict_types=1);

namespace Nexus\Localization\Enums;

/**
 * Locale status for draft/active/deprecated workflow.
 *
 * - Active: Available for user selection and resolution
 * - Draft: Admin-only visibility for testing CLDR data before rollout
 * - Deprecated: Maintained for backward compatibility but hidden from new selections
 */
enum LocaleStatus: string
{
    case Active = 'active';
    case Draft = 'draft';
    case Deprecated = 'deprecated';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Draft => 'Draft',
            self::Deprecated => 'Deprecated',
        };
    }

    /**
     * Check if locale is active.
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if locale is draft.
     */
    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if locale is deprecated.
     */
    public function isDeprecated(): bool
    {
        return $this === self::Deprecated;
    }

    /**
     * Check if locale is available for user selection.
     */
    public function isAvailableForUsers(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if locale is visible in admin UI.
     */
    public function isVisibleInAdmin(): bool
    {
        return true; // All statuses visible in admin
    }
}
