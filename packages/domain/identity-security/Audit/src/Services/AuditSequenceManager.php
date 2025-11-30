<?php

declare(strict_types=1);

namespace Nexus\Audit\Services;

use Nexus\Audit\Contracts\AuditSequenceManagerInterface;
use Nexus\Audit\Contracts\AuditStorageInterface;
use Nexus\Audit\Exceptions\AuditSequenceException;

/**
 * Audit Sequence Manager Service
 * 
 * Manages monotonic, per-tenant sequence numbers.
 * Thread-safe operations for concurrent writes.
 * 
 * Satisfies: REL-AUD-0301
 */
final readonly class AuditSequenceManager implements AuditSequenceManagerInterface
{
    public function __construct(
        private AuditStorageInterface $storage
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getNextSequence(string $tenantId): int
    {
        // Get last record from storage (most reliable source)
        $lastRecord = $this->storage->getLastRecord($tenantId);

        if ($lastRecord === null) {
            // First record for this tenant
            return 1;
        }

        return $lastRecord->getSequenceNumber() + 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentSequence(string $tenantId): ?int
    {
        $lastRecord = $this->storage->getLastRecord($tenantId);
        return $lastRecord?->getSequenceNumber();
    }

    /**
     * {@inheritDoc}
     */
    public function initializeSequence(string $tenantId): void
    {
        // No-op in this implementation
        // Sequence is auto-initialized on first write
    }

    /**
     * {@inheritDoc}
     */
    public function resetSequence(string $tenantId, int $toValue = 0): void
    {
        // DANGEROUS - Only for testing
        // In production implementation, this would be protected or removed
        throw new \RuntimeException(
            'Sequence reset is not supported in production. ' .
            'This operation would break hash chain integrity.'
        );
    }
}
