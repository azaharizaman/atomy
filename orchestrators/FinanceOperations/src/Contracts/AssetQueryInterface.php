<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Asset Query Interface for FinanceOperations.
 *
 * Defines the query methods needed by FinanceOperations DataProviders
 * to fetch asset data from the Assets package.
 */
interface AssetQueryInterface
{
    /**
     * Find an asset by ID.
     *
     * @param string $tenantId Tenant identifier
     * @param string $assetId Asset identifier
     * @return object|null Asset entity
     */
    public function find(string $tenantId, string $assetId): ?object;

    /**
     * Get aggregated net book value for reconciliation.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return object Aggregate net-book-value projection
     */
    public function getNetBookValueTotal(string $tenantId, string $periodId): object;

    /**
     * Get GL control account code for fixed assets.
     *
     * @param string $tenantId Tenant identifier
     * @return string|null Control account code or null when not configured
     */
    public function getControlAccountCode(string $tenantId): ?string;

    /**
     * Get unposted depreciation rows for reconciliation detail.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return iterable<int, object> Unposted depreciation projections
     */
    public function getUnpostedDepreciation(string $tenantId, string $periodId): iterable;
}
