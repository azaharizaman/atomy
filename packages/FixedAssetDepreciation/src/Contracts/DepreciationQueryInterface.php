<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Entities\AssetDepreciation;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;

/**
 * Interface for CQRS read operations on depreciation data.
 *
 * This interface defines all read-only query operations for depreciation
 * data. Following the CQRS pattern, these operations are side-effect-free
 * and can be called multiple times without state changes.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface DepreciationQueryInterface
{
    /**
     * Get depreciation for a specific asset and period.
     *
     * @param string $assetId The asset identifier
     * @param string $periodId The fiscal period identifier
     * @param DepreciationType|null $type Optional depreciation type (book/tax)
     * @return AssetDepreciation|null The depreciation record or null if not found
     */
    public function getDepreciationForPeriod(
        string $assetId,
        string $periodId,
        ?DepreciationType $type = null
    ): ?AssetDepreciation;

    /**
     * Get accumulated depreciation for an asset.
     *
     * Returns the total accumulated depreciation for an asset as of
     * a specific date or the current date.
     *
     * @param string $assetId The asset identifier
     * @param \DateTimeInterface|null $asOfDate The date to calculate accumulated depreciation
     * @param DepreciationType|null $type Optional depreciation type
     * @return DepreciationAmount The accumulated depreciation amount
     */
    public function getAccumulatedDepreciation(
        string $assetId,
        ?\DateTimeInterface $asOfDate = null,
        ?DepreciationType $type = null
    ): DepreciationAmount;

    /**
     * Get current net book value for an asset.
     *
     * Calculates the net book value (cost - accumulated depreciation)
     * as of a specific date.
     *
     * @param string $assetId The asset identifier
     * @param \DateTimeInterface|null $asOfDate The date to calculate book value
     * @return BookValue The book value as of the date
     */
    public function getNetBookValue(
        string $assetId,
        ?\DateTimeInterface $asOfDate = null
    ): BookValue;

    /**
     * Get depreciation expense for a specific period.
     *
     * Returns the total depreciation expense for a fiscal period
     * across all assets or filtered by criteria.
     *
     * @param string $periodId The fiscal period identifier
     * @param array $filters Optional filters:
     *                       - assetIds: array of asset IDs
     *                       - costCenterId: filter by cost center
     *                       - departmentId: filter by department
     *                       - type: DepreciationType (book/tax)
     * @return DepreciationAmount The total depreciation expense
     */
    public function getDepreciationExpenseForPeriod(
        string $periodId,
        array $filters = []
    ): DepreciationAmount;

    /**
     * Get depreciation history for an asset.
     *
     * Returns all depreciation records for an asset, optionally filtered
     * by date range or status.
     *
     * @param string $assetId The asset identifier
     * @param array $options Query options:
     *                      - startDate: \DateTimeInterface earliest date
     *                      - endDate: \DateTimeInterface latest date
     *                      - status: DepreciationStatus filter
     *                      - type: DepreciationType filter
     *                      - limit: int maximum records to return
     *                      - offset: int record offset for pagination
     * @return array<AssetDepreciation> Array of depreciation records
     */
    public function getDepreciationHistory(
        string $assetId,
        array $options = []
    ): array;

    /**
     * Get remaining depreciation for an asset.
     *
     * Calculates the total remaining depreciation that will be
     * recorded from a specific date until the end of the asset's
     * useful life.
     *
     * @param string $assetId The asset identifier
     * @param \DateTimeInterface|null $asOfDate The date to calculate from
     * @return DepreciationAmount The remaining depreciation amount
     */
    public function getRemainingDepreciation(
        string $assetId,
        ?\DateTimeInterface $asOfDate = null
    ): DepreciationAmount;

    /**
     * Get depreciation by cost center for a period.
     *
     * Returns aggregated depreciation expense broken down by
     * cost center for reporting purposes.
     *
     * @param string $periodId The fiscal period identifier
     * @param string|null $costCenterId Optional cost center filter
     * @return array<string, DepreciationAmount> Array keyed by cost center ID
     */
    public function getDepreciationByCostCenter(
        string $periodId,
        ?string $costCenterId = null
    ): array;

    /**
     * Get depreciation by department for a period.
     *
     * Returns aggregated depreciation expense broken down by
     * department for reporting purposes.
     *
     * @param string $periodId The fiscal period identifier
     * @param string|null $departmentId Optional department filter
     * @return array<string, DepreciationAmount> Array keyed by department ID
     */
    public function getDepreciationByDepartment(
        string $periodId,
        ?string $departmentId = null
    ): array;

    /**
     * Check if depreciation has been posted for a period.
     *
     * @param string $assetId The asset identifier
     * @param string $periodId The fiscal period identifier
     * @return bool True if depreciation has been posted
     */
    public function isDepreciationPosted(string $assetId, string $periodId): bool;

    /**
     * Get count of depreciation records.
     *
     * Returns the total count of depreciation records matching
     * the provided filters.
     *
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function getDepreciationCount(array $filters = []): int;

    /**
     * Get fully depreciated assets.
     *
     * Returns all assets that are fully depreciated (accumulated
     * depreciation equals or exceeds the depreciable amount).
     *
     * @param array $options Query options:
     *                      - asOfDate: \DateTimeInterface calculation date
     *                      - includeFullyDepreciated: bool include fully depreciated
     *                      - limit: int maximum records
     * @return array<string> Array of asset IDs
     */
    public function getFullyDepreciatedAssets(array $options = []): array;

    /**
     * Get depreciation summary for multiple assets.
     *
     * Returns aggregated depreciation data for multiple assets,
     * useful for reporting and batch operations.
     *
     * @param array<string> $assetIds Array of asset identifiers
     * @param string $periodId The fiscal period identifier
     * @return array<string, AssetDepreciation> Array keyed by asset ID
     */
    public function getDepreciationSummary(
        array $assetIds,
        string $periodId
    ): array;

    /**
     * Get last depreciation date for an asset.
     *
     * Returns the date of the most recent depreciation calculation
     * for an asset.
     *
     * @param string $assetId The asset identifier
     * @param DepreciationStatus|null $status Optional status filter
     * @return \DateTimeImmutable|null The last depreciation date or null
     */
    public function getLastDepreciationDate(
        string $assetId,
        ?DepreciationStatus $status = null
    ): ?\DateTimeImmutable;

    /**
     * Search depreciation records with flexible criteria.
     *
     * Provides a flexible search interface for depreciation records
     * with various filter combinations.
     *
     * @param array $criteria Search criteria:
     *                        - assetId: string
     *                        - scheduleId: string
     *                        - periodId: string
     *                        - status: DepreciationStatus
     *                        - type: DepreciationType
     *                        - startDate: \DateTimeInterface
     *                        - endDate: \DateTimeInterface
     *                        - minAmount: float
     *                        - maxAmount: float
     *                        - costCenterId: string
     *                        - departmentId: string
     * @param array $pagination Pagination options:
     *                         - limit: int (default: 50)
     *                         - offset: int (default: 0)
     *                         - sortBy: string
     *                         - sortOrder: 'asc'|'desc'
     * @return array<AssetDepreciation> Array of matching depreciation records
     */
    public function search(
        array $criteria = [],
        array $pagination = []
    ): array;
}
