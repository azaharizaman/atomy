<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MfaEnrollment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MfaEnrollment>
 */
final class MfaEnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MfaEnrollment::class);
    }

    /**
     * @return MfaEnrollment[]
     */
    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPrimaryByUserId(string $userId): ?MfaEnrollment
    {
        return $this->createQueryBuilder('m')
            ->where('m.userId = :userId')
            ->andWhere('m.primary = :primary')
            ->andWhere('m.verified = :verified')
            ->setParameter('userId', $userId)
            ->setParameter('primary', true)
            ->setParameter('verified', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserIdAndMethod(string $userId, string $method): ?MfaEnrollment
    {
        return $this->findOneBy(['userId' => $userId, 'method' => $method]);
    }

    public function hasVerifiedEnrollment(string $userId): bool
    {
        $count = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.userId = :userId')
            ->andWhere('m.verified = :verified')
            ->setParameter('userId', $userId)
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function deleteByUserId(string $userId): int
    {
        return $this->createQueryBuilder('m')
            ->delete()
            ->where('m.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
