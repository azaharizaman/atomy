<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 */
final class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function findByToken(string $token): ?Session
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * @return Session[]
     */
    public function findActiveByUserId(string $userId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.userId = :userId')
            ->andWhere('s.revokedAt IS NULL')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.lastActivityAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.expiresAt < :now')
            ->andWhere('s.revokedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function deleteByUserId(string $userId): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function revokeAllByUserId(string $userId): int
    {
        return $this->createQueryBuilder('s')
            ->update()
            ->set('s.revokedAt', ':now')
            ->where('s.userId = :userId')
            ->andWhere('s.revokedAt IS NULL')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function revokeAllExcept(string $userId, string $currentToken): int
    {
        return $this->createQueryBuilder('s')
            ->update()
            ->set('s.revokedAt', ':now')
            ->where('s.userId = :userId')
            ->andWhere('s.token != :currentToken')
            ->andWhere('s.revokedAt IS NULL')
            ->setParameter('userId', $userId)
            ->setParameter('currentToken', $currentToken)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * @return Session[]
     */
    public function findByDeviceFingerprint(string $userId, string $fingerprint): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.userId = :userId')
            ->andWhere('s.deviceFingerprint = :fingerprint')
            ->setParameter('userId', $userId)
            ->setParameter('fingerprint', $fingerprint)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findInactiveSessions(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.lastActivityAt < :since')
            ->andWhere('s.revokedAt IS NULL')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }
}
