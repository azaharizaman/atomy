<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Entities\LedgerAccount;

/**
 * Ledger Account Query Interface
 * 
 * Read-only operations for querying ledger account data.
 */
interface LedgerAccountQueryInterface
{
    /**
     * Find a ledger account by its ID
     * 
     * @param string $id LedgerAccount ULID
     * @return LedgerAccount|null The account if found
     */
    public function findById(string $id): ?LedgerAccount;

    /**
     * Find all accounts in a ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> All accounts in the ledger
     */
    public function findByLedger(string $ledgerId): array;

    /**
     * Find an account by its code within a ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $accountCode Account code (e.g., "1000-0000")
     * @return LedgerAccount|null The account if found
     */
    public function findByAccountCode(string $ledgerId, string $accountCode): ?LedgerAccount;

    /**
     * Find accounts that allow posting
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> Accounts that allow posting
     */
    public function findPostableAccounts(string $ledgerId): array;

    /**
     * Find bank/cash accounts
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> Bank accounts
     */
    public function findBankAccounts(string $ledgerId): array;

    /**
     * Find accounts by cost center
     * 
     * @param string $costCenterId Cost center ULID
     * @return array<LedgerAccount> Accounts assigned to the cost center
     */
    public function findByCostCenter(string $costCenterId): array;

    /**
     * Check if an account exists
     * 
     * @param string $id LedgerAccount ULID
     * @return bool True if account exists
     */
    public function exists(string $id): bool;

    /**
     * Check if an account allows posting
     * 
     * @param string $id LedgerAccount ULID
     * @return bool True if account allows posting
     */
    public function allowsPosting(string $id): bool;
}
