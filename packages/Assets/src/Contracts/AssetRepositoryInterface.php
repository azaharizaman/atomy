<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;

/**
 * Asset Repository Interface
 *
 * Persistence layer for asset records.
 */
interface AssetRepositoryInterface
{
    /**
     * Find asset by ID
     *
     * @param string $assetId Asset identifier
     * @return AssetInterface|null
     */
    public function findById(string $assetId): ?AssetInterface;

    /**
     * Find asset by tag
     *
     * @param string $tenantId Tenant identifier
     * @param string $assetTag Asset tag
     * @return AssetInterface|null
     */
    public function findByTag(string $tenantId, string $assetTag): ?AssetInterface;

    /**
     * Find assets by category
     *
     * @param string $categoryId Category identifier
     * @return array<AssetInterface>
     */
    public function findByCategory(string $categoryId): array;

    /**
     * Find assets assigned to user
     *
     * @param string $userId User identifier
     * @return array<AssetInterface>
     */
    public function findByAssignedUser(string $userId): array;

    /**
     * Find assets at location
     *
     * @param string $tenantId Tenant identifier
     * @param string $location Location (string or location ID)
     * @return array<AssetInterface>
     */
    public function findByLocation(string $tenantId, string $location): array;

    /**
     * Find assets due for depreciation
     *
     * Returns active assets that haven't been fully depreciated
     *
     * @param string $tenantId Tenant identifier
     * @param DateTimeInterface $periodEndDate Period end date
     * @return array<AssetInterface>
     */
    public function findAssetsDueForDepreciation(string $tenantId, DateTimeInterface $periodEndDate): array;

    /**
     * Find all active assets for tenant
     *
     * @param string $tenantId Tenant identifier
     * @return array<AssetInterface>
     */
    public function findActiveAssets(string $tenantId): array;

    /**
     * Save asset
     *
     * @param AssetInterface $asset Asset to save
     * @return void
     */
    public function save(AssetInterface $asset): void;

    /**
     * Update asset
     *
     * @param string $assetId Asset identifier
     * @param array $data Updated data
     * @return void
     */
    public function update(string $assetId, array $data): void;

    /**
     * Delete asset (soft delete)
     *
     * @param string $assetId Asset identifier
     * @return void
     */
    public function delete(string $assetId): void;
}
