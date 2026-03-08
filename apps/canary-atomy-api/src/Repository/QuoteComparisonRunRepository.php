<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\QuoteComparisonRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteComparisonRun>
 */
final class QuoteComparisonRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteComparisonRun::class);
    }

    public function findByIdAndTenant(string $id, string $tenantId): ?QuoteComparisonRun
    {
        return $this->findOneBy(['id' => $id, 'tenantId' => $tenantId]);
    }

    public function findByTenantRfqAndIdempotency(string $tenantId, string $rfqId, string $idempotencyKey): ?QuoteComparisonRun
    {
        return $this->findOneBy([
            'tenantId' => $tenantId,
            'rfqId' => $rfqId,
            'idempotencyKey' => $idempotencyKey,
        ]);
    }

    /**
     * @return array<QuoteComparisonRun>
     */
    public function findActiveByTenantAndRfq(string $tenantId, string $rfqId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.tenantId = :tenantId')
            ->andWhere('r.rfqId = :rfqId')
            ->andWhere('r.status NOT IN (:terminal)')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('rfqId', $rfqId)
            ->setParameter('terminal', [
                QuoteComparisonRun::STATUS_STALE,
                QuoteComparisonRun::STATUS_DISCARDED,
                QuoteComparisonRun::STATUS_REJECTED,
            ])
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find non-preview draft runs older than a cutoff for stale detection.
     *
     * @return array<QuoteComparisonRun>
     */
    public function findStaleDraftsBefore(\DateTimeImmutable $cutoff): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :draft')
            ->andWhere('r.isPreview = :notPreview')
            ->andWhere('r.createdAt < :cutoff')
            ->setParameter('draft', QuoteComparisonRun::STATUS_DRAFT)
            ->setParameter('notPreview', false)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find runs that have expired based on their expires_at column.
     *
     * @return array<QuoteComparisonRun>
     */
    public function findExpiredRuns(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.expiresAt IS NOT NULL')
            ->andWhere('r.expiresAt < :now')
            ->andWhere('r.status NOT IN (:terminal)')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('terminal', [
                QuoteComparisonRun::STATUS_APPROVED,
                QuoteComparisonRun::STATUS_REJECTED,
                QuoteComparisonRun::STATUS_STALE,
                QuoteComparisonRun::STATUS_DISCARDED,
            ])
            ->getQuery()
            ->getResult();
    }
}
