<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Depreciation Manager Interface for FinanceOperations.
 *
 * Defines the methods needed by FinanceOperations DataProviders
 * to interact with the FixedAssetDepreciation package.
 */
interface DepreciationManagerInterface
{
    /**
     * Get depreciation runs by period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return iterable Depreciation run entities
     */
    public function getRunsByPeriod(string $tenantId, string $periodId): iterable;

    /**
     * Get book value for an asset.
     *
     * @param string $tenantId Tenant identifier
     * @param string $assetId Asset identifier
     * @return object Book value entity with accumulated_depreciation, net_book_value, remaining_life
     */
    public function getBookValue(string $tenantId, string $assetId): object;

    /**
     * Get depreciation schedules for an asset.
     *
     * @param string $tenantId Tenant identifier
     * @param string $assetId Asset identifier
     * @return iterable Depreciation schedule entities
     */
    public function getSchedules(string $tenantId, string $assetId): iterable;
}
