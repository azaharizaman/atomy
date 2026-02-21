<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts\Integration;

use Nexus\FixedAssetDepreciation\Enums\DepreciationType;

/**
 * Interface for journal entry integration.
 *
 * This interface defines the contract for integrating with the
 * Nexus\JournalEntry package to create and manage journal entries
 * for depreciation transactions.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts\Integration
 */
interface JournalEntryProviderInterface
{
    /**
     * Create depreciation journal entry.
     *
     * Creates a journal entry to record depreciation expense
     * and accumulated depreciation.
     *
     * @param string $assetId The asset identifier
     * @param string $periodId The fiscal period identifier
     * @param float $depreciationAmount The depreciation amount
     * @param string $expenseAccountId The expense account ID
     * @param string $accumulatedDepreciationAccountId The accumulated depreciation account ID
     * @param string|null $description Optional entry description
     * @param DepreciationType $type The depreciation type
     * @return string The created journal entry ID
     */
    public function createDepreciationEntry(
        string $assetId,
        string $periodId,
        float $depreciationAmount,
        string $expenseAccountId,
        string $accumulatedDepreciationAccountId,
        ?string $description = null,
        DepreciationType $type = DepreciationType::BOOK
    ): string;

    /**
     * Reverse a depreciation journal entry.
     *
     * Creates a reversing journal entry for previously posted
     * depreciation.
     *
     * @param string $originalEntryId The original journal entry ID
     * @param string $reason The reason for reversal
     * @return string The created reversing entry ID
     */
    public function reverseDepreciationEntry(
        string $originalEntryId,
        string $reason
    ): string;

    /**
     * Create revaluation journal entry.
     *
     * Creates a journal entry to record asset revaluation
     * (IFRS IAS 16 model).
     *
     * @param string $assetId The asset identifier
     * @param string $revaluationId The revaluation identifier
     * @param float $revaluationAmount The revaluation amount
     * @param string $assetAccountId The asset account ID
     * @param string $reserveAccountId The revaluation reserve account ID
     * @param string $reason The reason for revaluation
     * @param bool $isDecrement True if this is a decrement (decrease)
     * @return string The created journal entry ID
     */
    public function createRevaluationEntry(
        string $assetId,
        string $revaluationId,
        float $revaluationAmount,
        string $assetAccountId,
        string $reserveAccountId,
        string $reason,
        bool $isDecrement = false
    ): string;

    /**
     * Create disposal journal entry.
     *
     * Creates a journal entry to record asset disposal,
     * including removal of cost, accumulated depreciation,
     * and recognition of gain/loss.
     *
     * @param string $assetId The asset identifier
     * @param float $cost The original cost
     * @param float $accumulatedDepreciation The accumulated depreciation
     * @param float $disposalProceeds The proceeds from disposal
     * @param string $cashAccountId The cash/receivable account ID
     * @param string $assetAccountId The asset cost account ID
     * @param string $accumulatedDepreciationAccountId The accumulated depreciation account ID
     * @param string $gainLossAccountId The gain/loss account ID
     * @param string $reason The reason for disposal
     * @return string The created journal entry ID
     */
    public function createDisposalEntry(
        string $assetId,
        float $cost,
        float $accumulatedDepreciation,
        float $disposalProceeds,
        string $cashAccountId,
        string $assetAccountId,
        string $accumulatedDepreciationAccountId,
        string $gainLossAccountId,
        string $reason
    ): string;

    /**
     * Create depreciation adjustment journal entry.
     *
     * Creates a journal entry to correct previously recorded
     * depreciation.
     *
     * @param string $assetId The asset identifier
     * @param string $periodId The fiscal period identifier
     * @param float $adjustmentAmount The adjustment amount (positive or negative)
     * @param string $expenseAccountId The expense account ID
     * @param string $accumulatedDepreciationAccountId The accumulated depreciation account ID
     * @param string $reason The reason for adjustment
     * @return string The created journal entry ID
     */
    public function createAdjustmentEntry(
        string $assetId,
        string $periodId,
        float $adjustmentAmount,
        string $expenseAccountId,
        string $accumulatedDepreciationAccountId,
        string $reason
    ): string;

    /**
     * Check if journal entry is posted.
     *
     * @param string $entryId The journal entry identifier
     * @return bool True if the entry is posted
     */
    public function isPosted(string $entryId): bool;

    /**
     * Get journal entry status.
     *
     * @param string $entryId The journal entry identifier
     * @return string The entry status
     */
    public function getEntryStatus(string $entryId): string;

    /**
     * Validate journal entry can be created.
     *
     * Checks if all required accounts exist and are valid
     * before creating the entry.
     *
     * @param array $accountIds Array of account identifiers
     * @return array<string> Array of validation errors
     */
    public function validateAccounts(array $accountIds): array;

    /**
     * Get journal entry by ID.
     *
     * @param string $entryId The journal entry identifier
     * @return array|null The journal entry data or null
     */
    public function getEntry(string $entryId): ?array;

    /**
     * Batch create depreciation entries.
     *
     * Creates multiple depreciation journal entries in a
     * single transaction for efficiency.
     *
     * @param array $entries Array of entry data
     * @return array<string> Array of created entry IDs
     */
    public function createBatchDepreciationEntries(array $entries): array;
}
