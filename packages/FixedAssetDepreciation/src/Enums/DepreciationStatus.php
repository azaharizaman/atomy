<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Enums;

/**
 * Status of a depreciation calculation.
 *
 * @enum
 */
enum DepreciationStatus: string
{
    case CALCULATED = 'calculated';
    case POSTED = 'posted';
    case REVERSED = 'reversed';
    case ADJUSTED = 'adjusted';

    /**
     * Check if this status allows further processing.
     */
    public function canBePosted(): bool
    {
        return $this === self::CALCULATED;
    }

    /**
     * Check if this status can be reversed.
     */
    public function canBeReversed(): bool
    {
        return $this === self::POSTED;
    }

    /**
     * Check if this status is final.
     */
    public function isFinal(): bool
    {
        return $this === self::REVERSED;
    }
}
