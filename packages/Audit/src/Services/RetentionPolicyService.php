<?php

declare(strict_types=1);

namespace Nexus\Audit\Services;

use Nexus\Audit\Contracts\AuditStorageInterface;

/**
 * Service for managing retention policies and purging expired audit records
 * 
 * Compliance-driven retention management ensuring legal requirements are met.
 * Satisfies: BUS-AUD-0151, FUN-AUD-0194
 *
 * @package Nexus\Audit\Services
 */
final readonly class RetentionPolicyService
{
    public function __construct(
        private AuditStorageInterface $storage
    ) {}

    /**
     * Purge expired audit records
     * 
     * WARNING: This is the ONLY operation that can delete audit records.
     * Only expired records (past retention period) can be deleted.
     * 
     * Satisfies: BUS-AUD-0151
     *
     * @param \DateTimeImmutable|null $beforeDate If null, uses current date
     * @param int $batchSize Number of records to delete per batch
     * @return int Total number of records deleted
     */
    public function purgeExpiredLogs(
        ?\DateTimeImmutable $beforeDate = null,
        int $batchSize = 1000
    ): int {
        $totalDeleted = 0;
        $beforeDate = $beforeDate ?? new \DateTimeImmutable();

        do {
            // Get batch of expired records
            $expiredRecords = $this->storage->findExpired($beforeDate, $batchSize);

            if (empty($expiredRecords)) {
                break;
            }

            // Extract IDs
            $ids = array_map(fn($record) => $record->getId(), $expiredRecords);

            // Delete batch
            $deleted = $this->storage->deleteExpired($ids);
            $totalDeleted += $deleted;

            // If we deleted less than batch size, we're done
            if ($deleted < $batchSize) {
                break;
            }

        } while (true);

        return $totalDeleted;
    }

    /**
     * Get count of expired logs without deleting
     *
     * @param \DateTimeImmutable|null $beforeDate
     * @return int
     */
    public function countExpiredLogs(?\DateTimeImmutable $beforeDate = null): int
    {
        $beforeDate = $beforeDate ?? new \DateTimeImmutable();
        return $this->storage->countExpired($beforeDate);
    }

    /**
     * Preview logs that will be purged
     *
     * @param \DateTimeImmutable|null $beforeDate
     * @param int $limit
     * @return array
     */
    public function previewExpiredLogs(
        ?\DateTimeImmutable $beforeDate = null,
        int $limit = 100
    ): array {
        $beforeDate = $beforeDate ?? new \DateTimeImmutable();
        return $this->storage->findExpired($beforeDate, $limit);
    }
}
