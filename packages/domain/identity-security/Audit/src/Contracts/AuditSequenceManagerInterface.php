<?php

declare(strict_types=1);

namespace Nexus\Audit\Contracts;

/**
 * Audit Sequence Manager Interface
 * 
 * Manages monotonic, per-tenant sequence numbers for audit records.
 * Ensures no gaps or duplicates in the sequence.
 * 
 * Satisfies: REL-AUD-0301 (Log sequence integrity)
 */
interface AuditSequenceManagerInterface
{
    /**
     * Get next sequence number for tenant
     * 
     * Thread-safe operation using database locking or atomic increment.
     * 
     * @param string $tenantId
     * @return int Next sequence number
     * 
     * @throws \Nexus\Audit\Exceptions\AuditSequenceException
     */
    public function getNextSequence(string $tenantId): int;

    /**
     * Get current sequence number for tenant (without incrementing)
     * 
     * @param string $tenantId
     * @return int|null Current sequence, or null if no records exist
     */
    public function getCurrentSequence(string $tenantId): ?int;

    /**
     * Initialize sequence for new tenant
     * 
     * @param string $tenantId
     * @return void
     */
    public function initializeSequence(string $tenantId): void;

    /**
     * Reset sequence (DANGEROUS - use only for testing)
     * 
     * @param string $tenantId
     * @param int $toValue
     * @return void
     */
    public function resetSequence(string $tenantId, int $toValue = 0): void;
}
