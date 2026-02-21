<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Entities\AssetRevaluation;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;
use Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;

/**
 * Interface for revaluation query operations.
 *
 * This interface defines all read-only query operations for asset
 * revaluations. It provides methods for retrieving revaluation
 * history, calculating revaluation impacts, and querying revaluation data.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface RevaluationQueryInterface
{
    /**
     * Get revaluation by ID.
     *
     * @param string $revaluationId The revaluation identifier
     * @return AssetRevaluation|null The revaluation or null
     */
    public function getById(string $revaluationId): ?AssetRevaluation;

    /**
     * Get revaluation for an asset at a specific date.
     *
     * Returns the most recent revaluation on or before the given date.
     *
     * @param string $assetId The asset identifier
     * @param \DateTimeInterface|null $asOfDate The date to query
     * @return AssetRevaluation|null The revaluation or null
     */
    public function getForAssetAtDate(string $assetId, ?\DateTimeInterface $asOfDate = null): ?AssetRevaluation;

    /**
     * Get all revaluations for an asset.
     *
     * Returns complete revaluation history for an asset.
     *
     * @param string $assetId The asset identifier
     * @param array $options Query options:
     *                      - sortOrder: 'asc'|'desc' (default: 'desc')
     *                      - limit: int
     *                      - includeReversed: bool
     * @return array<AssetRevaluation> Array of revaluations
     */
    public function getAllForAsset(string $assetId, array $options = []): array;

    /**
     * Get revaluation history count for an asset.
     *
     * @param string $assetId The asset identifier
     * @return int Total number of revaluations
     */
    public function getHistoryCount(string $assetId): int;

    /**
     * Calculate total revaluation amount for an asset.
     *
     * Returns the cumulative revaluation adjustment from all
     * revaluations for an asset.
     *
     * @param string $assetId The asset identifier
     * @param \DateTimeInterface|null $asOfDate Calculate up to this date
     * @return RevaluationAmount The total revaluation amount
     */
    public function getTotalRevaluationAmount(
        string $assetId,
        ?\DateTimeInterface $asOfDate = null
    ): RevaluationAmount;

    /**
     * Get current book value after revaluation.
     *
     * Returns the book value of an asset considering the most
     * recent revaluation.
     *
     * @param string $assetId The asset identifier
     * @return BookValue The current book value
     */
    public function getCurrentBookValue(string $assetId): BookValue;

    /**
     * Get revaluation by type for an asset.
     *
     * Returns all revaluations of a specific type (increment/decrement).
     *
     * @param string $assetId The asset identifier
     * @param RevaluationType $type The revaluation type
     * @return array<AssetRevaluation> Array of matching revaluations
     */
    public function getByType(string $assetId, RevaluationType $type): array;

    /**
     * Check if asset has been revalued.
     *
     * @param string $assetId The asset identifier
     * @return bool True if the asset has been revalued
     */
    public function hasBeenRevalued(string $assetId): bool;

    /**
     * Get revaluation reserve balance.
     *
     * Returns the total revaluation reserve (equity) for an asset,
     * following IFRS IAS 16 revaluation model.
     *
     * @param string $assetId The asset identifier
     * @return float The revaluation reserve balance
     */
    public function getRevaluationReserve(string $assetId): float;

    /**
     * Get revaluations for a date range.
     *
     * @param \DateTimeInterface $startDate Start of date range
     * @param \DateTimeInterface $endDate End of date range
     * @param array $filters Optional filters:
     *                       - assetIds: array of asset IDs
     *                       - type: RevaluationType
     *                       - reason: string
     * @return array<AssetRevaluation> Matching revaluations
     */
    public function getForDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        array $filters = []
    ): array;

    /**
     * Get pending revaluations.
     *
     * Returns revaluations that have been created but not yet
     * posted to the GL.
     *
     * @return array<AssetRevaluation> Array of pending revaluations
     */
    public function getPending(): array;

    /**
     * Search revaluations with flexible criteria.
     *
     * @param array $criteria Search criteria:
     *                       - assetId: string
     *                       - type: RevaluationType
     *                       - startDate: \DateTimeInterface
     *                       - endDate: \DateTimeInterface
     *                       - minAmount: float
     *                       - maxAmount: float
     *                       - glAccountId: string
     *                       - reason: string (partial match)
     * @param array $pagination Pagination: limit, offset
     * @return array<AssetRevaluation> Matching revaluations
     */
    public function search(
        array $criteria = [],
        array $pagination = []
    ): array;

    /**
     * Get revaluation impact summary.
     *
     * Returns aggregated impact of revaluations for multiple assets,
     * useful for financial reporting.
     *
     * @param array<string> $assetIds Array of asset identifiers
     * @param \DateTimeInterface|null $asOfDate Calculate up to this date
     * @return array{
     *     totalIncrement: float,
     *     totalDecrement: float,
     *     netRevaluation: float,
     *     revaluationReserve: float,
     *     assetCount: int
     * }
     */
    public function getImpactSummary(
        array $assetIds,
        ?\DateTimeInterface $asOfDate = null
    ): array;

    /**
     * Get last revaluation date for an asset.
     *
     * @param string $assetId The asset identifier
     * @return \DateTimeImmutable|null The last revaluation date
     */
    public function getLastRevaluationDate(string $assetId): ?\DateTimeImmutable;

    /**
     * Calculate gain/loss from revaluation decrement.
     *
     * For decrements, calculates the impact on equity and determines
     * if it should offset previous revaluation reserve or be expensed.
     *
     * @param string $assetId The asset identifier
     * @param string $revaluationId The revaluation to evaluate
     * @return array{
     *     amount: float,
     *     offsetFromReserve: float,
     *     expenseAmount: float,
     *     reason: string
     * }
     */
    public function calculateDecrementImpact(
        string $assetId,
        string $revaluationId
    ): array;
}
