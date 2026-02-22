<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for depreciation data provider.
 *
 * This interface defines the data retrieval methods needed by the
 * DepreciationCoordinator to perform depreciation operations.
 *
 * Interface Segregation Compliance:
 * - Defines only data retrieval methods, no business logic
 * - Used by coordinators to aggregate data from multiple sources
 * - Implementation provided by adapters layer
 */
interface DepreciationDataProviderInterface
{
    /**
     * Get depreciation run summary for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Depreciation run summary data
     */
    public function getDepreciationRunSummary(string $tenantId, string $periodId): array;

    /**
     * Get asset book values for depreciation calculation.
     *
     * @param string $tenantId The tenant identifier
     * @param array<string> $assetIds List of asset identifiers
     * @return array<string, mixed> Asset book values data
     */
    public function getAssetBookValues(string $tenantId, array $assetIds): array;

    /**
     * Get depreciation schedules for an asset.
     *
     * @param string $tenantId The tenant identifier
     * @param string $assetId The asset identifier
     * @return array<string, mixed> Depreciation schedule data
     */
    public function getDepreciationSchedules(string $tenantId, string $assetId): array;
}
