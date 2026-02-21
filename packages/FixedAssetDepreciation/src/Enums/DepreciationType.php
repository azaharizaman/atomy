<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Enums;

/**
 * Depreciation type - Book (financial reporting) or Tax (income tax).
 *
 * @enum
 */
enum DepreciationType: string
{
    case BOOK = 'book';
    case TAX = 'tax';

    /**
     * Get the display name for this type.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::BOOK => 'Book Depreciation',
            self::TAX => 'Tax Depreciation',
        };
    }

    /**
     * Check if this type requires GL posting.
     */
    public function requiresGlPosting(): bool
    {
        return $this === self::BOOK;
    }
}
