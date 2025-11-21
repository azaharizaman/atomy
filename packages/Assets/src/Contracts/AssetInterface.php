<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;
use Nexus\Assets\Enums\AssetStatus;
use Nexus\Assets\Enums\DepreciationMethod;

/**
 * Asset Entity Interface
 *
 * Represents a fixed asset with progressive complexity support.
 * Tier 1: Basic tracking with string location and user assignment
 * Tier 2+: Supports LocationInterface and advanced tracking
 */
interface AssetInterface
{
    /**
     * Get unique identifier (ULID)
     */
    public function getId(): string;

    /**
     * Get tenant identifier
     */
    public function getTenantId(): string;

    /**
     * Get asset tag (unique identifier for physical tracking)
     */
    public function getAssetTag(): string;

    /**
     * Get asset category identifier
     */
    public function getCategoryId(): string;

    /**
     * Get asset description
     */
    public function getDescription(): string;

    /**
     * Get acquisition cost in functional currency
     */
    public function getAcquisitionCost(): float;

    /**
     * Get acquisition date
     */
    public function getAcquisitionDate(): DateTimeInterface;

    /**
     * Get depreciation method
     */
    public function getDepreciationMethod(): DepreciationMethod;

    /**
     * Get useful life in years (decimal for partial years)
     */
    public function getUsefulLifeYears(): float;

    /**
     * Get salvage value (residual value at end of useful life)
     */
    public function getSalvageValue(): float;

    /**
     * Get current asset status
     */
    public function getStatus(): AssetStatus;

    /**
     * Get location (string for Tier 1, LocationInterface for Tier 2+)
     * 
     * @return string|object String for Tier 1, LocationInterface for Tier 2/3
     */
    public function getLocation(): string|object;

    /**
     * Get assigned user ID (simple string for Tier 1)
     */
    public function getAssignedTo(): ?string;

    /**
     * Get purchase order ID (link to Sales/Finance transaction)
     */
    public function getPurchaseOrderId(): ?string;

    /**
     * Get vendor ID
     */
    public function getVendorId(): ?string;

    /**
     * Get current accumulated depreciation
     */
    public function getAccumulatedDepreciation(): float;

    /**
     * Get current net book value (acquisition cost - accumulated depreciation)
     */
    public function getNetBookValue(): float;

    /**
     * Get acquisition currency (for multi-currency Tier 3)
     */
    public function getAcquisitionCurrency(): ?string;

    /**
     * Get functional currency
     */
    public function getFunctionalCurrency(): ?string;

    /**
     * Get exchange rate at acquisition (for multi-currency Tier 3)
     */
    public function getExchangeRateAtAcquisition(): ?float;

    /**
     * Get acquisition cost in functional currency (Tier 3)
     */
    public function getAcquisitionCostInFunctionalCurrency(): float;

    /**
     * Get disposal date (if disposed)
     */
    public function getDisposalDate(): ?DateTimeInterface;

    /**
     * Get disposal method
     */
    public function getDisposalMethod(): ?string;

    /**
     * Get disposal proceeds
     */
    public function getDisposalProceeds(): ?float;

    /**
     * Get metadata (JSON field for tier-specific extensions)
     */
    public function getMetadata(): array;

    /**
     * Get created timestamp
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * Get updated timestamp
     */
    public function getUpdatedAt(): DateTimeInterface;

    /**
     * Check if asset is active
     */
    public function isActive(): bool;

    /**
     * Check if asset is fully depreciated
     */
    public function isFullyDepreciated(): bool;

    /**
     * Check if asset can be depreciated
     */
    public function canDepreciate(): bool;
}
