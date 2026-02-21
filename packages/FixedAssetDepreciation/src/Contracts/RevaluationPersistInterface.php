<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Entities\AssetRevaluation;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;

/**
 * Interface for revaluation write operations.
 *
 * This interface defines all write operations for asset revaluations,
 * including creation, reversal, and posting of revaluations.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface RevaluationPersistInterface
{
    /**
     * Create a new revaluation.
     *
     * Creates a new asset revaluation record following IFRS IAS 16
     * revaluation model.
     *
     * @param string $assetId The asset identifier
     * @param float $previousValue The previous book value
     * @param float $newValue The new book value after revaluation
     * @param float $salvageValue The salvage value
     * @param RevaluationType $type The revaluation type (increment/decrement)
     * @param \DateTimeInterface $revaluationDate The date of revaluation
     * @param string $reason The reason for revaluation
     * @param string|null $glAccountId Optional GL account for revaluation reserve
     * @param array $options Additional options
     * @return AssetRevaluation The created revaluation
     */
    public function create(
        string $assetId,
        float $previousValue,
        float $newValue,
        float $salvageValue,
        RevaluationType $type,
        \DateTimeInterface $revaluationDate,
        string $reason,
        ?string $glAccountId = null,
        array $options = []
    ): AssetRevaluation;

    /**
     * Save a revaluation record.
     *
     * @param AssetRevaluation $revaluation The revaluation to save
     * @return AssetRevaluation The saved revaluation
     */
    public function save(AssetRevaluation $revaluation): AssetRevaluation;

    /**
     * Reverse a revaluation.
     *
     * Creates a reversal entry to undo a previous revaluation.
     * Typically used when revaluation was recorded in error.
     *
     * @param string $revaluationId The revaluation to reverse
     * @param string $reason The reason for reversal
     * @return AssetRevaluation The reversal revaluation
     */
    public function reverse(string $revaluationId, string $reason): AssetRevaluation;

    /**
     * Post revaluation to GL.
     *
     * Marks a revaluation as posted to the general ledger.
     *
     * @param string $revaluationId The revaluation identifier
     * @param string $journalEntryId The journal entry identifier
     * @return AssetRevaluation The updated revaluation
     */
    public function post(string $revaluationId, string $journalEntryId): AssetRevaluation;

    /**
     * Update revaluation status.
     *
     * Changes the status of a revaluation record.
     *
     * @param string $revaluationId The revaluation identifier
     * @param string $status The new status
     * @return AssetRevaluation The updated revaluation
     */
    public function updateStatus(string $revaluationId, string $status): AssetRevaluation;

    /**
     * Delete a revaluation.
     *
     * Removes a revaluation record. Only allowed for unposted
     * revaluations.
     *
     * @param string $revaluationId The revaluation identifier
     * @return bool True if deleted
     */
    public function delete(string $revaluationId): bool;

    /**
     * Link depreciation schedule to revaluation.
     *
     * Associates a depreciation schedule with the revaluation for
     * future depreciation calculations.
     *
     * @param string $revaluationId The revaluation identifier
     * @param string $scheduleId The depreciation schedule identifier
     * @return AssetRevaluation The updated revaluation
     */
    public function linkSchedule(string $revaluationId, string $scheduleId): AssetRevaluation;

    /**
     * Check if revaluation can be modified.
     *
     * @param string $revaluationId The revaluation identifier
     * @param string $operation The intended operation
     * @return bool True if modification is allowed
     */
    public function canModify(string $revaluationId, string $operation): bool;

    /**
     * Batch create revaluations.
     *
     * Creates multiple revaluations in a single transaction.
     *
     * @param array $revaluations Array of revaluation data
     * @return array<AssetRevaluation> Created revaluations
     */
    public function createBatch(array $revaluations): array;

    /**
     * Update revaluation GL account.
     *
     * Updates the GL account associated with revaluation reserve.
     *
     * @param string $revaluationId The revaluation identifier
     * @param string $glAccountId The GL account identifier
     * @return AssetRevaluation The updated revaluation
     */
    public function updateGlAccount(string $revaluationId, string $glAccountId): AssetRevaluation;

    /**
     * Lock revaluation for processing.
     *
     * Prevents concurrent modifications during batch operations.
     *
     * @param string $revaluationId The revaluation identifier
     * @param string $processId The process identifier
     * @return bool True if lock acquired
     */
    public function lock(string $revaluationId, string $processId): bool;

    /**
     * Unlock revaluation.
     *
     * @param string $revaluationId The revaluation identifier
     * @param string $processId The process identifier
     * @return bool True if lock released
     */
    public function unlock(string $revaluationId, string $processId): bool;
}
