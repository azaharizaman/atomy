<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Entities\Ledger;

/**
 * Ledger Persist Interface
 * 
 * Write operations for ledger data.
 * Part of the CQRS pattern separating read and write operations.
 */
interface LedgerPersistInterface
{
    /**
     * Save a ledger
     * 
     * @param Ledger $ledger The ledger to save
     * @return void
     */
    public function save(Ledger $ledger): void;

    /**
     * Delete a ledger
     * 
     * Note: Deleting a ledger with transactions may not be allowed
     * depending on business rules.
     * 
     * @param string $id Ledger ULID
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Update ledger status
     * 
     * @param string $id Ledger ULID
     * @param \Nexus\GeneralLedger\Enums\LedgerStatus $status New status
     * @return void
     */
    public function updateStatus(string $id, \Nexus\GeneralLedger\Enums\LedgerStatus $status): void;
}
