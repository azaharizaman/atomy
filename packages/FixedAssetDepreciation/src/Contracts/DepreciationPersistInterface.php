<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Entities\AssetDepreciation;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;

/**
 * Interface for CQRS write operations on depreciation data.
 *
 * This interface defines all write operations for depreciation data,
 * including creating, updating, and deleting depreciation records.
 * Following CQRS, these operations handle state changes.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface DepreciationPersistInterface
{
    /**
     * Save a depreciation record.
     *
     * Creates or updates a depreciation record in the data store.
     *
     * @param AssetDepreciation $depreciation The depreciation record to save
     * @return AssetDepreciation The saved depreciation record
     */
    public function save(AssetDepreciation $depreciation): AssetDepreciation;

    /**
     * Save multiple depreciation records in a batch.
     *
     * Optimized for bulk operations, this method saves multiple
     * depreciation records in a single transaction.
     *
     * @param array<AssetDepreciation> $depreciations Array of depreciation records
     * @return array<AssetDepreciation> The saved depreciation records
     */
    public function saveBatch(array $depreciations): array;

    /**
     * Update depreciation status.
     *
     * Changes the status of a depreciation record, typically from
     * CALCULATED to POSTED after GL posting.
     *
     * @param string $depreciationId The depreciation identifier
     * @param DepreciationStatus $status The new status
     * @return AssetDepreciation The updated depreciation record
     */
    public function updateStatus(string $depreciationId, DepreciationStatus $status): AssetDepreciation;

    /**
     * Delete a depreciation record.
     *
     * Removes a depreciation record from the data store.
     * Typically only allowed for CALCULATED status records.
     *
     * @param string $depreciationId The depreciation identifier
     * @return bool True if deleted successfully
     */
    public function delete(string $depreciationId): bool;

    /**
     * Delete multiple depreciation records.
     *
     * Batch delete operation for multiple depreciation records.
     *
     * @param array<string> $depreciationIds Array of depreciation identifiers
     * @return int Number of records deleted
     */
    public function deleteBatch(array $depreciationIds): int;

    /**
     * Reverse a depreciation record.
     *
     * Creates a reversal entry for a previously posted depreciation.
     * The original record is marked as REVERSED and a new record
     * is created to offset the original amount.
     *
     * @param string $depreciationId The depreciation identifier to reverse
     * @param string $reason The reason for reversal
     * @return AssetDepreciation The new reversal depreciation record
     */
    public function reverse(string $depreciationId, string $reason): AssetDepreciation;

    /**
     * Update depreciation amount.
     *
     * Modifies the depreciation amount for an existing record.
     * Typically only allowed for CALCULATED status.
     *
     * @param string $depreciationId The depreciation identifier
     * @param float $newAmount The new depreciation amount
     * @return AssetDepreciation The updated depreciation record
     */
    public function updateAmount(string $depreciationId, float $newAmount): AssetDepreciation;

    /**
     * Add posting date to depreciation record.
     *
     * Records the date when depreciation was posted to the GL.
     *
     * @param string $depreciationId The depreciation identifier
     * @param \DateTimeImmutable $postingDate The posting date
     * @return AssetDepreciation The updated depreciation record
     */
    public function setPostingDate(string $depreciationId, \DateTimeImmutable $postingDate): AssetDepreciation;

    /**
     * Link journal entry to depreciation record.
     *
     * Associates a journal entry ID with the depreciation record
     * for audit and traceability purposes.
     *
     * @param string $depreciationId The depreciation identifier
     * @param string $journalEntryId The journal entry identifier
     * @return AssetDepreciation The updated depreciation record
     */
    public function linkJournalEntry(string $depreciationId, string $journalEntryId): AssetDepreciation;

    /**
     * Check if depreciation can be modified.
     *
     * Determines whether a depreciation record can be modified
     * based on its current status.
     *
     * @param string $depreciationId The depreciation identifier
     * @param string $operation The intended operation (update, delete, reverse)
     * @return bool True if the operation is allowed
     */
    public function canModify(string $depreciationId, string $operation): bool;

    /**
     * Get pending depreciation records for posting.
     *
     * Retrieves all depreciation records with CALCULATED status
     * that are ready for GL posting.
     *
     * @param string $periodId Optional period filter
     * @return array<AssetDepreciation> Array of pending depreciation records
     */
    public function getPendingForPosting(?string $periodId = null): array;

    /**
     * Create depreciation adjustment.
     *
     * Creates an adjustment entry to correct previously calculated
     * depreciation. This can be positive or negative.
     *
     * @param string $originalDepreciationId The original depreciation identifier
     * @param float $adjustmentAmount The adjustment amount (positive or negative)
     * @param string $reason The reason for the adjustment
     * @param string $adjustedBy The user or system that made the adjustment
     * @return AssetDepreciation The new adjustment depreciation record
     */
    public function createAdjustment(
        string $originalDepreciationId,
        float $adjustmentAmount,
        string $reason,
        string $adjustedBy
    ): AssetDepreciation;

    /**
     * Recalculate accumulated depreciation for an asset.
     *
     * Recomputes the accumulated depreciation based on all
     * current depreciation records for an asset.
     *
     * @param string $assetId The asset identifier
     * @return float The recalculated accumulated depreciation
     */
    public function recalculateAccumulatedDepreciation(string $assetId): float;

    /**
     * Lock depreciation record for processing.
     *
     * Prevents concurrent modifications to a depreciation record
     * during batch processing.
     *
     * @param string $depreciationId The depreciation identifier
     * @param string $processId The process identifier requesting the lock
     * @return bool True if lock was acquired
     */
    public function lock(string $depreciationId, string $processId): bool;

    /**
     * Release lock on depreciation record.
     *
     * @param string $depreciationId The depreciation identifier
     * @param string $processId The process identifier that holds the lock
     * @return bool True if lock was released
     */
    public function unlock(string $depreciationId, string $processId): bool;
}
