<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Services;

use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;

/**
 * Service for managing retention policies and purging expired logs
 * Satisfies: BUS-AUD-0151, FUN-AUD-0194
 *
 * @package Nexus\AuditLogger\Services
 */
class RetentionPolicyService
{
    private AuditLogRepositoryInterface $repository;

    public function __construct(AuditLogRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Purge expired audit logs
     * Satisfies: BUS-AUD-0151
     *
     * @param \DateTimeInterface|null $beforeDate If null, uses current date
     * @param int $batchSize Number of records to delete per batch
     * @return int Total number of records deleted
     */
    public function purgeExpiredLogs(
        ?\DateTimeInterface $beforeDate = null,
        int $batchSize = 1000
    ): int {
        $totalDeleted = 0;
        $beforeDate = $beforeDate ?? new \DateTime();

        do {
            $deleted = $this->repository->deleteExpired($beforeDate);
            $totalDeleted += $deleted;

            // Break if no more records to delete
            if ($deleted === 0) {
                break;
            }

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
     * @param \DateTimeInterface|null $beforeDate
     * @return int
     */
    public function countExpiredLogs(?\DateTimeInterface $beforeDate = null): int
    {
        $beforeDate = $beforeDate ?? new \DateTime();
        $expired = $this->repository->getExpired($beforeDate, 1);
        
        // Get statistics to get total count
        $stats = $this->repository->getStatistics([
            'date_to' => $beforeDate->format('Y-m-d H:i:s'),
            'expired_only' => true
        ]);

        return $stats['total_count'] ?? 0;
    }

    /**
     * Preview logs that will be purged
     *
     * @param \DateTimeInterface|null $beforeDate
     * @param int $limit
     * @return array
     */
    public function previewExpiredLogs(
        ?\DateTimeInterface $beforeDate = null,
        int $limit = 100
    ): array {
        $beforeDate = $beforeDate ?? new \DateTime();
        return $this->repository->getExpired($beforeDate, $limit);
    }
}
