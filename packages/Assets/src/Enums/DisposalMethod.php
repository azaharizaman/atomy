<?php

declare(strict_types=1);

namespace Nexus\Assets\Enums;

/**
 * Disposal Method Enum
 *
 * Defines methods for disposing of fixed assets.
 */
enum DisposalMethod: string
{
    case SALE = 'sale';
    case SCRAP = 'scrap';
    case DONATION = 'donation';
    case TRADE = 'trade';
    case LOSS = 'loss';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::SALE => 'Sale',
            self::SCRAP => 'Scrap/Discard',
            self::DONATION => 'Donation',
            self::TRADE => 'Trade-In',
            self::LOSS => 'Loss/Theft',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match($this) {
            self::SALE => 'Asset sold to third party with proceeds received',
            self::SCRAP => 'Asset discarded or scrapped with minimal/no value',
            self::DONATION => 'Asset donated to charity or non-profit',
            self::TRADE => 'Asset traded for another asset (part exchange)',
            self::LOSS => 'Asset lost, stolen, or destroyed',
        };
    }

    /**
     * Check if method typically has proceeds
     */
    public function hasProceeds(): bool
    {
        return match($this) {
            self::SALE, self::TRADE => true,
            self::SCRAP, self::DONATION, self::LOSS => false,
        };
    }

    /**
     * Check if method requires documentation
     */
    public function requiresDocumentation(): bool
    {
        return match($this) {
            self::DONATION, self::LOSS => true,
            default => false,
        };
    }

    /**
     * Get GL impact description (for Tier 3)
     */
    public function getGLImpact(): string
    {
        return match($this) {
            self::SALE => 'DR Cash, DR Accumulated Depreciation, CR Asset, CR/DR Gain/Loss',
            self::SCRAP => 'DR Accumulated Depreciation, DR Loss on Disposal, CR Asset',
            self::DONATION => 'DR Accumulated Depreciation, DR Donation Expense, CR Asset',
            self::TRADE => 'DR New Asset, DR Accumulated Depreciation, CR Old Asset, CR/DR Gain/Loss',
            self::LOSS => 'DR Accumulated Depreciation, DR Loss on Disposal, CR Asset',
        };
    }
}
