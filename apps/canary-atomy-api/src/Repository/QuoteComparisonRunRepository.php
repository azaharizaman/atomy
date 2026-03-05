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
}
