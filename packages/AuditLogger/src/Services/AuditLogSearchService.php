<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Services;

use Nexus\AuditLogger\Contracts\AuditLogInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;

/**
 * Service for searching and filtering audit logs
 * Satisfies: FUN-AUD-0189, FUN-AUD-0190
 *
 * @package Nexus\AuditLogger\Services
 */
class AuditLogSearchService
{
    private AuditLogRepositoryInterface $repository;

    public function __construct(AuditLogRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Search audit logs with comprehensive filters
     * Satisfies: FUN-AUD-0189 (full-text search), FUN-AUD-0190 (filtering)
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDirection
     * @return array
     */
    public function search(
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): array {
        return $this->repository->search($filters, $page, $perPage, $sortBy, $sortDirection);
    }

    /**
     * Search by entity/subject
     *
     * @param string $subjectType
     * @param int|string $subjectId
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function searchBySubject(string $subjectType, int|string $subjectId, int $limit = 100): array
    {
        return $this->repository->getBySubject($subjectType, $subjectId, $limit);
    }

    /**
     * Search by user/causer
     *
     * @param string $causerType
     * @param int|string $causerId
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function searchByCauser(string $causerType, int|string $causerId, int $limit = 100): array
    {
        return $this->repository->getByCauser($causerType, $causerId, $limit);
    }

    /**
     * Search by batch UUID
     * Satisfies: BUS-AUD-0150
     *
     * @param string $batchUuid
     * @return AuditLogInterface[]
     */
    public function searchByBatch(string $batchUuid): array
    {
        return $this->repository->getByBatchUuid($batchUuid);
    }

    /**
     * Search by audit level
     * Satisfies: FUN-AUD-0195
     *
     * @param int $level
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function searchByLevel(int $level, int $limit = 100): array
    {
        return $this->repository->getByLevel($level, $limit);
    }

    /**
     * Search by tenant
     * Satisfies: FUN-AUD-0188
     *
     * @param int|string $tenantId
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function searchByTenant(int|string $tenantId, int $limit = 100): array
    {
        return $this->repository->getByTenant($tenantId, $limit);
    }

    /**
     * Get activity statistics
     * Satisfies: FUN-AUD-0199
     *
     * @param array $filters
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->repository->getStatistics($filters);
    }
}
