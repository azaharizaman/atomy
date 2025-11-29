<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\AuditLogger\Contracts\AuditLogInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Repository for audit logs implementing Nexus AuditLogger contract.
 * 
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository implements AuditLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * Create a new audit log entry.
     */
    public function create(array $data): AuditLogInterface
    {
        $log = new AuditLog();
        $log->setId($data['id'] ?? (string) new Ulid());
        $log->setLogName($data['log_name']);
        $log->setDescription($data['description']);
        
        if (isset($data['subject_type'])) {
            $log->setSubjectType($data['subject_type']);
        }
        if (isset($data['subject_id'])) {
            $log->setSubjectId($data['subject_id']);
        }
        if (isset($data['causer_type'])) {
            $log->setCauserType($data['causer_type']);
        }
        if (isset($data['causer_id'])) {
            $log->setCauserId($data['causer_id']);
        }
        if (isset($data['properties'])) {
            $log->setProperties($data['properties']);
        }
        if (isset($data['event'])) {
            $log->setEvent($data['event']);
        }
        if (isset($data['level'])) {
            $log->setLevel($data['level']);
        }
        if (isset($data['batch_uuid'])) {
            $log->setBatchUuid($data['batch_uuid']);
        }
        if (isset($data['ip_address'])) {
            $log->setIpAddress($data['ip_address']);
        }
        if (isset($data['user_agent'])) {
            $log->setUserAgent($data['user_agent']);
        }
        if (isset($data['tenant_id'])) {
            $log->setTenantId($data['tenant_id']);
        }
        if (isset($data['retention_days'])) {
            $log->setRetentionDays($data['retention_days']);
        }
        if (isset($data['created_at'])) {
            $log->setCreatedAt(
                $data['created_at'] instanceof \DateTimeImmutable 
                    ? $data['created_at'] 
                    : new \DateTimeImmutable($data['created_at'])
            );
        }
        if (isset($data['expires_at'])) {
            $log->setExpiresAt(
                $data['expires_at'] instanceof \DateTimeImmutable 
                    ? $data['expires_at'] 
                    : new \DateTimeImmutable($data['expires_at'])
            );
        }

        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();

        return $log;
    }

    /**
     * Find audit log by ID.
     */
    public function findById($id): ?AuditLogInterface
    {
        return $this->find($id);
    }

    /**
     * Search audit logs with filters.
     */
    public function search(
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): array {
        $qb = $this->createQueryBuilder('a');

        // Apply filters
        if (!empty($filters['log_name'])) {
            $qb->andWhere('a.logName = :log_name')
               ->setParameter('log_name', $filters['log_name']);
        }
        if (!empty($filters['subject_type'])) {
            $qb->andWhere('a.subjectType = :subject_type')
               ->setParameter('subject_type', $filters['subject_type']);
        }
        if (!empty($filters['subject_id'])) {
            $qb->andWhere('a.subjectId = :subject_id')
               ->setParameter('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['causer_type'])) {
            $qb->andWhere('a.causerType = :causer_type')
               ->setParameter('causer_type', $filters['causer_type']);
        }
        if (!empty($filters['causer_id'])) {
            $qb->andWhere('a.causerId = :causer_id')
               ->setParameter('causer_id', $filters['causer_id']);
        }
        if (!empty($filters['event'])) {
            $qb->andWhere('a.event = :event')
               ->setParameter('event', $filters['event']);
        }
        if (!empty($filters['level'])) {
            $qb->andWhere('a.level = :level')
               ->setParameter('level', $filters['level']);
        }
        if (!empty($filters['tenant_id'])) {
            $qb->andWhere('a.tenantId = :tenant_id')
               ->setParameter('tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['batch_uuid'])) {
            $qb->andWhere('a.batchUuid = :batch_uuid')
               ->setParameter('batch_uuid', $filters['batch_uuid']);
        }
        if (!empty($filters['date_from'])) {
            $dateFrom = $filters['date_from'] instanceof \DateTimeInterface 
                ? $filters['date_from'] 
                : new \DateTimeImmutable($filters['date_from']);
            $qb->andWhere('a.createdAt >= :date_from')
               ->setParameter('date_from', $dateFrom);
        }
        if (!empty($filters['date_to'])) {
            $dateTo = $filters['date_to'] instanceof \DateTimeInterface 
                ? $filters['date_to'] 
                : new \DateTimeImmutable($filters['date_to']);
            $qb->andWhere('a.createdAt <= :date_to')
               ->setParameter('date_to', $dateTo);
        }
        if (!empty($filters['description'])) {
            $qb->andWhere('a.description LIKE :description')
               ->setParameter('description', '%' . $filters['description'] . '%');
        }
        if (!empty($filters['search'])) {
            $qb->andWhere('(a.description LIKE :search OR a.logName LIKE :search)')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Count total
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        // Apply sorting
        $sortField = match ($sortBy) {
            'log_name' => 'a.logName',
            'level' => 'a.level',
            'event' => 'a.event',
            default => 'a.createdAt',
        };
        $qb->orderBy($sortField, strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC');

        // Apply pagination
        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);

        return [
            'data' => $qb->getQuery()->getResult(),
            'total' => $total,
        ];
    }

    /**
     * Get audit logs for a specific subject entity.
     */
    public function getBySubject(string $subjectType, $subjectId, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.subjectType = :type')
            ->andWhere('a.subjectId = :id')
            ->setParameter('type', $subjectType)
            ->setParameter('id', (string) $subjectId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get audit logs by causer.
     */
    public function getByCauser(string $causerType, $causerId, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.causerType = :type')
            ->andWhere('a.causerId = :id')
            ->setParameter('type', $causerType)
            ->setParameter('id', (string) $causerId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get audit logs by batch UUID.
     */
    public function getByBatchUuid(string $batchUuid): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.batchUuid = :uuid')
            ->setParameter('uuid', $batchUuid)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get audit logs by level.
     */
    public function getByLevel(int $level, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.level = :level')
            ->setParameter('level', $level)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get audit logs by tenant ID.
     */
    public function getByTenant($tenantId, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.tenantId = :tenant')
            ->setParameter('tenant', (string) $tenantId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get expired audit logs for purging.
     */
    public function getExpired(?\DateTimeInterface $beforeDate = null, int $limit = 1000): array
    {
        $beforeDate = $beforeDate ?? new \DateTimeImmutable();
        
        return $this->createQueryBuilder('a')
            ->where('a.expiresAt <= :before')
            ->setParameter('before', $beforeDate)
            ->orderBy('a.expiresAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete expired audit logs.
     */
    public function deleteExpired(?\DateTimeInterface $beforeDate = null): int
    {
        $beforeDate = $beforeDate ?? new \DateTimeImmutable();
        
        return (int) $this->createQueryBuilder('a')
            ->delete()
            ->where('a.expiresAt <= :before')
            ->setParameter('before', $beforeDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete audit logs by IDs.
     */
    public function deleteByIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return (int) $this->createQueryBuilder('a')
            ->delete()
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    /**
     * Get activity statistics.
     */
    public function getStatistics(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('a');

        // Apply same filters as search
        if (!empty($filters['tenant_id'])) {
            $qb->andWhere('a.tenantId = :tenant_id')
               ->setParameter('tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['date_from'])) {
            $dateFrom = $filters['date_from'] instanceof \DateTimeInterface 
                ? $filters['date_from'] 
                : new \DateTimeImmutable($filters['date_from']);
            $qb->andWhere('a.createdAt >= :date_from')
               ->setParameter('date_from', $dateFrom);
        }
        if (!empty($filters['date_to'])) {
            $dateTo = $filters['date_to'] instanceof \DateTimeInterface 
                ? $filters['date_to'] 
                : new \DateTimeImmutable($filters['date_to']);
            $qb->andWhere('a.createdAt <= :date_to')
               ->setParameter('date_to', $dateTo);
        }

        // Total count
        $total = (int) (clone $qb)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        // By log name
        $byLogName = $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'SELECT log_name, COUNT(*) as count FROM audit_logs GROUP BY log_name ORDER BY count DESC'
            )
            ->fetchAllKeyValue();

        // By level
        $byLevel = $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'SELECT level, COUNT(*) as count FROM audit_logs GROUP BY level ORDER BY level'
            )
            ->fetchAllKeyValue();

        return [
            'total_count' => $total,
            'by_log_name' => $byLogName,
            'by_level' => array_map('intval', $byLevel),
        ];
    }

    /**
     * Export audit logs to array format.
     */
    public function exportToArray(array $filters = [], int $limit = 10000): array
    {
        $qb = $this->createQueryBuilder('a');

        // Apply filters (same as search)
        if (!empty($filters['log_name'])) {
            $qb->andWhere('a.logName = :log_name')
               ->setParameter('log_name', $filters['log_name']);
        }
        if (!empty($filters['subject_type'])) {
            $qb->andWhere('a.subjectType = :subject_type')
               ->setParameter('subject_type', $filters['subject_type']);
        }
        if (!empty($filters['subject_id'])) {
            $qb->andWhere('a.subjectId = :subject_id')
               ->setParameter('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['causer_type'])) {
            $qb->andWhere('a.causerType = :causer_type')
               ->setParameter('causer_type', $filters['causer_type']);
        }
        if (!empty($filters['causer_id'])) {
            $qb->andWhere('a.causerId = :causer_id')
               ->setParameter('causer_id', $filters['causer_id']);
        }
        if (!empty($filters['tenant_id'])) {
            $qb->andWhere('a.tenantId = :tenant_id')
               ->setParameter('tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['date_from'])) {
            $dateFrom = $filters['date_from'] instanceof \DateTimeInterface 
                ? $filters['date_from'] 
                : new \DateTimeImmutable($filters['date_from']);
            $qb->andWhere('a.createdAt >= :date_from')
               ->setParameter('date_from', $dateFrom);
        }
        if (!empty($filters['date_to'])) {
            $dateTo = $filters['date_to'] instanceof \DateTimeInterface 
                ? $filters['date_to'] 
                : new \DateTimeImmutable($filters['date_to']);
            $qb->andWhere('a.createdAt <= :date_to')
               ->setParameter('date_to', $dateTo);
        }

        $qb->orderBy('a.createdAt', 'DESC')
           ->setMaxResults($limit);

        $logs = $qb->getQuery()->getResult();

        return array_map(fn (AuditLog $log) => $log->toArray(), $logs);
    }
}
