<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;

/**
 * Depreciation Record Interface
 *
 * Represents the result of a depreciation calculation for a specific period.
 * Tracks opening balance, depreciation amount, and closing balance.
 */
interface DepreciationRecordInterface
{
    /**
     * Get unique identifier
     */
    public function getId(): string;

    /**
     * Get asset identifier
     */
    public function getAssetId(): string;

    /**
     * Get tenant identifier
     */
    public function getTenantId(): string;

    /**
     * Get period start date
     */
    public function getPeriodStartDate(): DateTimeInterface;

    /**
     * Get period end date
     */
    public function getPeriodEndDate(): DateTimeInterface;

    /**
     * Get opening book value (at period start)
     */
    public function getOpeningBookValue(): float;

    /**
     * Get depreciation amount for this period
     */
    public function getDepreciationAmount(): float;

    /**
     * Get accumulated depreciation (total to date)
     */
    public function getAccumulatedDepreciation(): float;

    /**
     * Get closing book value (at period end)
     */
    public function getClosingBookValue(): float;

    /**
     * Get calculation method used
     */
    public function getMethod(): string;

    /**
     * Get GL journal entry ID (if posted to ledger, Tier 3)
     */
    public function getGlJournalId(): ?string;

    /**
     * Get units consumed (for Units of Production method)
     */
    public function getUnitsConsumed(): ?float;

    /**
     * Get calculation notes/details
     */
    public function getNotes(): ?string;

    /**
     * Get created timestamp
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * Check if this record has been posted to GL
     */
    public function isPostedToGL(): bool;
}
