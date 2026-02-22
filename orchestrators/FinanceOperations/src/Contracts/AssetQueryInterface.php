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
}
