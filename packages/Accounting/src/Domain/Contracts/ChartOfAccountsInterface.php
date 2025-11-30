<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\AccountCode;

/**
 * Interface for Chart of Accounts management.
 *
 * The Chart of Accounts provides a hierarchical structure for organizing
 * all financial accounts within an organization. This interface defines
 * operations for managing account structures and classifications.
 */
interface ChartOfAccountsInterface
{
    /**
     * Get the root accounts (top-level) in the chart.
     *
     * @return array<AccountInterface>
     */
    public function getRootAccounts(): array;

    /**
     * Get all accounts under a parent account.
     *
     * @return array<AccountInterface>
     */
    public function getChildAccounts(string $parentAccountId): array;

    /**
     * Find an account by its code.
     */
    public function findByCode(AccountCode $code): ?AccountInterface;

    /**
     * Get all accounts of a specific type.
     *
     * @return array<AccountInterface>
     */
    public function getAccountsByType(string $accountType): array;

    /**
     * Get the full path from root to account.
     *
     * @return array<AccountInterface>
     */
    public function getAccountPath(string $accountId): array;

    /**
     * Validate that an account code is unique within the chart.
     */
    public function isCodeUnique(AccountCode $code, ?string $excludeAccountId = null): bool;

    /**
     * Get all active accounts in the chart.
     *
     * @return array<AccountInterface>
     */
    public function getActiveAccounts(): array;
}
