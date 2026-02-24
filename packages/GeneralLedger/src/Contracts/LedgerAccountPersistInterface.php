<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Entities\LedgerAccount;

/**
 * Ledger Account Persist Interface
 * 
 * Write operations for ledger account data.
 */
interface LedgerAccountPersistInterface
{
    /**
     * Save a ledger account
     * 
     * @param LedgerAccount $account The account to save
     * @return void
     */
    public function save(LedgerAccount $account): void;

    /**
     * Delete a ledger account
     * 
     * Note: Deleting an account with transactions may not be allowed.
     * 
     * @param string $id LedgerAccount ULID
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Update account status (active/inactive)
     * 
     * @param string $id LedgerAccount ULID
     * @param bool $isActive Whether account is active
     * @return void
     */
    public function updateStatus(string $id, bool $isActive): void;

    /**
     * Update allow posting flag
     * 
     * @param string $id LedgerAccount ULID
     * @param bool $allowPosting Whether posting is allowed
     * @return void
     */
    public function updateAllowPosting(string $id, bool $allowPosting): void;

    /**
     * Assign cost center to account
     * 
     * @param string $id LedgerAccount ULID
     * @param string|null $costCenterId Cost center ULID (null to remove)
     * @return void
     */
    public function assignCostCenter(string $id, ?string $costCenterId): void;
}
