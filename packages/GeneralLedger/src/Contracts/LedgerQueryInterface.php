<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Enums\LedgerStatus;
use Nexus\GeneralLedger\Enums\LedgerType;

/**
 * Ledger Query Interface
 * 
 * Read-only operations for querying ledger data.
 * Part of the CQRS pattern separating read and write operations.
 */
interface LedgerQueryInterface
{
    /**
     * Find a ledger by its ID
     * 
     * @param string $id Ledger ULID
     * @return Ledger|null The ledger if found
     */
    public function findById(string $id): ?Ledger;

    /**
     * Find all ledgers for a tenant
     * 
     * @param string $tenantId Tenant ULID
     * @return array<Ledger> Ledgers belonging to the tenant
     */
    public function findByTenant(string $tenantId): array;

    /**
     * Find all active ledgers for a tenant
     * 
     * Only returns ledgers with ACTIVE status that can accept transactions.
     * 
     * @param string $tenantId Tenant ULID
     * @return array<Ledger> Active ledgers
     */
    public function findActiveByTenant(string $tenantId): array;

    /**
     * Find ledgers by type
     * 
     * @param string $tenantId Tenant ULID
     * @param LedgerType $type Ledger type (STATUTORY or MANAGEMENT)
     * @return array<Ledger> Ledgers of the specified type
     */
    public function findByType(string $tenantId, LedgerType $type): array;

    /**
     * Find ledgers by status
     * 
     * @param string $tenantId Tenant ULID
     * @param LedgerStatus $status Ledger status
     * @return array<Ledger> Ledgers with the specified status
     */
    public function findByStatus(string $tenantId, LedgerStatus $status): array;

    /**
     * Check if a ledger exists
     * 
     * @param string $id Ledger ULID
     * @return bool True if ledger exists
     */
    public function exists(string $id): bool;

    /**
     * Count ledgers for a tenant
     * 
     * @param string $tenantId Tenant ULID
     * @return int Number of ledgers
     */
    public function countByTenant(string $tenantId): int;
}
