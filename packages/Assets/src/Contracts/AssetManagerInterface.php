<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;
use Nexus\Assets\Enums\DisposalMethod;

/**
 * Asset Manager Interface
 *
 * Main service for asset lifecycle management with progressive complexity.
 * Tier 1: Basic acquisition, assignment, disposal
 * Tier 2: Adds warranty, maintenance, location tracking
 * Tier 3: Adds GL posting, physical audits, barcode generation
 */
interface AssetManagerInterface
{
    /**
     * Acquire a new asset (Tier 1)
     *
     * @param array $data Asset data
     * @return AssetInterface Created asset
     * @throws \Nexus\Assets\Exceptions\InvalidAssetDataException
     */
    public function acquireAsset(array $data): AssetInterface;

    /**
     * Assign asset to user (Tier 1)
     *
     * @param string $assetId Asset identifier
     * @param string $userId User identifier
     * @return void
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function assignAsset(string $assetId, string $userId): void;

    /**
     * Dispose of asset (Tier 1)
     *
     * Calculates gain/loss and publishes AssetDisposedEvent.
     * For Tier 3, triggers automatic GL posting via event listener.
     *
     * @param string $assetId Asset identifier
     * @param DisposalMethod $method Disposal method
     * @param float $saleProceeds Proceeds from sale (if applicable)
     * @return float Gain or loss on disposal
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     * @throws \Nexus\Assets\Exceptions\DisposalNotAllowedException
     */
    public function disposeAsset(
        string $assetId,
        DisposalMethod $method,
        float $saleProceeds = 0.0
    ): float;

    /**
     * Update asset location (Tier 1: string, Tier 2+: LocationInterface)
     *
     * @param string $assetId Asset identifier
     * @param string|object $location Location (string for Tier 1, LocationInterface for Tier 2/3)
     * @return void
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function updateLocation(string $assetId, string|object $location): void;

    /**
     * Track maintenance record (Tier 2+)
     *
     * @param MaintenanceRecordInterface $record Maintenance record
     * @return void
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function trackMaintenance(MaintenanceRecordInterface $record): void;

    /**
     * Enable warranty tracking (Tier 2+ fluent API)
     *
     * @param WarrantyRecordInterface $warranty Warranty record
     * @return self
     */
    public function withWarranty(WarrantyRecordInterface $warranty): self;

    /**
     * Enable automatic GL posting (Tier 3 fluent API)
     *
     * Sets flag for AssetGLListener to post journal entries
     *
     * @return self
     */
    public function withLedgerPost(): self;

    /**
     * Schedule physical audit (Tier 3)
     *
     * @param string $assetId Asset identifier
     * @param DateTimeInterface $auditDate Scheduled audit date
     * @return void
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function schedulePhysicalAudit(string $assetId, DateTimeInterface $auditDate): void;

    /**
     * Generate barcode data URL for asset tag (Tier 3)
     *
     * @param string $assetId Asset identifier
     * @return string Data URL for barcode image
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function generateBarcodeDataUrl(string $assetId): string;

    /**
     * Recalculate depreciation schedule (after useful life or salvage value change)
     *
     * @param string $assetId Asset identifier
     * @param array $updates Updated values (useful_life_years, salvage_value)
     * @return void
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     * @throws \Nexus\Assets\Exceptions\InvalidAssetDataException
     */
    public function recalculateSchedule(string $assetId, array $updates): void;

    /**
     * Get asset by ID
     *
     * @param string $assetId Asset identifier
     * @return AssetInterface
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function getAsset(string $assetId): AssetInterface;

    /**
     * Get assets by category
     *
     * @param string $categoryId Category identifier
     * @return array<AssetInterface>
     */
    public function getAssetsByCategory(string $categoryId): array;

    /**
     * Get assets assigned to user
     *
     * @param string $userId User identifier
     * @return array<AssetInterface>
     */
    public function getAssetsByUser(string $userId): array;
}
