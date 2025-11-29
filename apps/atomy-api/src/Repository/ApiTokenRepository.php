<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiToken>
 */
final class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function findByHash(string $hash): ?ApiToken
    {
        return $this->findOneBy(['tokenHash' => $hash]);
    }

    /**
     * @return ApiToken[]
     */
    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ApiToken[]
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.expiresAt IS NOT NULL')
            ->andWhere('t.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function deleteByUserId(string $userId): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt IS NOT NULL')
            ->andWhere('t.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
