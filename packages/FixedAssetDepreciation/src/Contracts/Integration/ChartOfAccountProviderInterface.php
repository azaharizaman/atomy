<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts\Integration;

use Nexus\FixedAssetDepreciation\Enums\DepreciationType;

/**
 * Interface for chart of account integration.
 *
 * This interface defines the contract for integrating with the
 * Nexus\ChartOfAccount package to retrieve GL account mappings
 * for depreciation transactions.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts\Integration
 */
interface ChartOfAccountProviderInterface
{
    /**
     * Get depreciation expense account for an asset.
     *
     * Returns the GL account used to record depreciation expense
     * for the specified asset.
     *
     * @param string $assetId The asset identifier
     * @param DepreciationType $type The depreciation type (book/tax)
     * @return string|null The GL account ID or null if not configured
     */
    public function getDepreciationExpenseAccount(
        string $assetId,
        DepreciationType $type = DepreciationType::BOOK
    ): ?string;

    /**
     * Get accumulated depreciation account for an asset.
     *
     * Returns the GL account used to record accumulated depreciation
     * (contra-asset account) for the specified asset.
     *
     * @param string $assetId The asset identifier
     * @param DepreciationType $type The depreciation type (book/tax)
     * @return string|null The GL account ID or null if not configured
     */
    public function getAccumulatedDepreciationAccount(
        string $assetId,
        DepreciationType $type = DepreciationType::BOOK
    ): ?string;

    /**
     * Get revaluation reserve account for an asset.
     *
     * Returns the GL account used to record revaluation reserve
     * (equity) following IFRS IAS 16.
     *
     * @param string $assetId The asset identifier
     * @return string|null The GL account ID or null if not configured
     */
    public function getRevaluationReserveAccount(string $assetId): ?string;

    /**
     * Get asset cost account for an asset category.
     *
     * Returns the GL account where the asset cost is recorded.
     *
     * @param string $assetCategoryId The asset category identifier
     * @return string|null The GL account ID or null
     */
    public function getAssetCostAccount(string $assetCategoryId): ?string;

    /**
     * Get disposal gain/loss account.
     *
     * Returns the GL account for recording gain or loss on
     * asset disposal.
     *
     * @param string $assetId The asset identifier
     * @param bool $isGain True for gain account, false for loss
     * @return string|null The GL account ID or null
     */
    public function getDisposalAccount(string $assetId, bool $isGain): ?string;

    /**
     * Get impairment loss account.
     *
     * Returns the GL account for recording asset impairment losses.
     *
     * @param string $assetId The asset identifier
     * @return string|null The GL account ID or null
     */
    public function getImpairmentLossAccount(string $assetId): ?string;

    /**
     * Validate GL account exists.
     *
     * Checks if the specified GL account exists and is active.
     *
     * @param string $accountId The GL account identifier
     * @return bool True if the account exists and is active
     */
    public function accountExists(string $accountId): bool;

    /**
     * Validate GL account type.
     *
     * Checks if the GL account is of the expected type
     * (asset, liability, equity, expense).
     *
     * @param string $accountId The GL account identifier
     * @param string $expectedType The expected account type
     * @return bool True if the account type matches
     */
    public function isAccountType(string $accountId, string $expectedType): bool;

    /**
     * Get default depreciation accounts.
     *
     * Returns the system's default depreciation accounts
     * when asset-specific accounts are not configured.
     *
     * @param DepreciationType $type The depreciation type
     * @return array{
     *     expenseAccount: string|null,
     *     accumulatedDepreciationAccount: string|null
     * }
     */
    public function getDefaultAccounts(DepreciationType $type = DepreciationType::BOOK): array;

    /**
     * Check if accounts are configured for asset.
     *
     * @param string $assetId The asset identifier
     * @param DepreciationType $type The depreciation type
     * @return bool True if accounts are configured
     */
    public function hasConfiguredAccounts(string $assetId, DepreciationType $type = DepreciationType::BOOK): bool;
}
