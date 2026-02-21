<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Entities\AssetRevaluation;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;

/**
 * Interface for asset revaluation operations.
 *
 * This is the primary facade interface for all asset revaluation
 * operations. It provides a unified API for revaluing assets
 * following IFRS IAS 16 revaluation model.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface AssetRevaluationInterface
{
    /**
     * Revalue an asset.
     *
     * Performs a complete revaluation of an asset, updating its book
     * value and creating a revaluation record. This also triggers
     * recalculation of future depreciation.
     *
     * @param string $assetId The asset identifier
     * @param float $newCost The new asset cost/revalued amount
     * @param float $newSalvageValue The new salvage value
     * @param RevaluationType $type The revaluation type
     * @param string $reason The reason for revaluation
     * @param \DateTimeInterface|null $revaluationDate The effective date
     * @param string|null $glAccountId Optional GL account for revaluation reserve
     * @return AssetRevaluation The created revaluation record
     */
    public function revalue(
        string $assetId,
        float $newCost,
        float $newSalvageValue,
        RevaluationType $type,
        string $reason,
        ?\DateTimeInterface $revaluationDate = null,
        ?string $glAccountId = null
    ): AssetRevaluation;

    /**
     * Reverse a revaluation.
     *
     * Reverses a previous revaluation, restoring the asset's book
     * value to its previous state.
     *
     * @param string $revaluationId The revaluation to reverse
     * @param string $reason The reason for reversal
     * @return AssetRevaluation The reversal revaluation record
     */
    public function reverse(string $revaluationId, string $reason): AssetRevaluation;

    /**
     * Calculate revaluation impact.
     *
     * Calculates the financial impact of a proposed revaluation
     * without actually performing the revaluation.
     *
     * @param string $assetId The asset identifier
     * @param float $proposedValue The proposed new value
     * @return array{
     *     previousValue: float,
     *     newValue: float,
     *     revaluationAmount: float,
     *     revaluationType: RevaluationType,
     *     depreciationImpact: float,
     *     annualDepreciationChange: float,
     *     revaluationReserveImpact: float
     * }
     */
    public function calculateImpact(
        string $assetId,
        float $proposedValue
    ): array;

    /**
     * Get current book value after revaluation.
     *
     * Returns the current book value of an asset considering
     * the most recent revaluation.
     *
     * @param string $assetId The asset identifier
     * @return BookValue The current book value
     */
    public function getCurrentBookValue(string $assetId): BookValue;

    /**
     * Get revaluation history.
     *
     * Returns all revaluation records for an asset.
     *
     * @param string $assetId The asset identifier
     * @param array $options Query options
     * @return array<AssetRevaluation> Array of revaluations
     */
    public function getHistory(string $assetId, array $options = []): array;

    /**
     * Post revaluation to GL.
     *
     * Posts a revaluation to the general ledger, creating
     * the necessary journal entries.
     *
     * @param string $revaluationId The revaluation identifier
     * @return AssetRevaluation The updated revaluation
     */
    public function postToGl(string $revaluationId): AssetRevaluation;

    /**
     * Process full revaluation (IFRS model).
     *
     * Performs a complete IFRS IAS 16 compliant revaluation,
     * including:
     * - Asset revaluation
     * - Depreciation recalculation
     * - Revaluation reserve adjustment
     * - GL posting
     *
     * @param string $assetId The asset identifier
     * @param float $fairValue The fair market value
     * @param float $salvageValue The salvage value
     * @param string $reason The reason for revaluation
     * @param string $revaluationReserveAccount The GL account for revaluation reserve
     * @param string|null $depreciationExpenseAccount Optional expense account for decrements
     * @return AssetRevaluation The revaluation record
     */
    public function processFullRevaluation(
        string $assetId,
        float $fairValue,
        float $salvageValue,
        string $reason,
        string $revaluationReserveAccount,
        ?string $depreciationExpenseAccount = null
    ): AssetRevaluation;

    /**
     * Recalculate depreciation after revaluation.
     *
     * Recalculates future depreciation based on the revalued amount
     * and remaining useful life.
     *
     * @param string $assetId The asset identifier
     * @param string $revaluationId The revaluation identifier
     * @return void
     */
    public function recalculateDepreciation(
        string $assetId,
        string $revaluationId
    ): void;

    /**
     * Validate revaluation parameters.
     *
     * Validates that the revaluation parameters are valid and
     * follow accounting standards.
     *
     * @param string $assetId The asset identifier
     * @param float $newValue The proposed new value
     * @param float $newSalvageValue The proposed salvage value
     * @return array<string> Array of validation errors
     */
    public function validate(
        string $assetId,
        float $newValue,
        float $newSalvageValue
    ): array;

    /**
     * Get revaluation reserve balance.
     *
     * Returns the total revaluation reserve for an asset.
     *
     * @param string $assetId The asset identifier
     * @return float The revaluation reserve balance
     */
    public function getReserveBalance(string $assetId): float;

    /**
     * Check if asset can be revalued.
     *
     * @param string $assetId The asset identifier
     * @return bool True if the asset can be revalued
     */
    public function canRevalue(string $assetId): bool;

    /**
     * Get revaluation for specific period.
     *
     * Returns the revaluation that was effective during a
     * specific period.
     *
     * @param string $assetId The asset identifier
     * @param string $periodId The period identifier
     * @return AssetRevaluation|null The revaluation or null
     */
    public function getForPeriod(string $assetId, string $periodId): ?AssetRevaluation;
}
