<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LegalHold;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Document\Contracts\LegalHoldInterface;
use Nexus\Document\Contracts\LegalHoldRepositoryInterface;

/**
 * @extends ServiceEntityRepository<LegalHold>
 */
class LegalHoldRepository extends ServiceEntityRepository implements LegalHoldRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalHold::class);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?LegalHoldInterface
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByDocumentId(string $documentId): array
    {
        return $this->findBy(['documentId' => $documentId]);
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveByDocumentId(string $documentId): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.documentId = :docId')
            ->andWhere('h.releasedAt IS NULL')
            ->setParameter('docId', $documentId)
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function hasActiveHold(string $documentId): bool
    {
        $count = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.documentId = :docId')
            ->andWhere('h.releasedAt IS NULL')
            ->setParameter('docId', $documentId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function findAllActive(): array
    {
        return $this->findBy(['releasedAt' => null]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByMatterReference(string $matterReference): array
    {
        return $this->findBy(['matterReference' => $matterReference]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByAppliedBy(string $userId): array
    {
        return $this->findBy(['appliedBy' => $userId]);
    }

    /**
     * {@inheritdoc}
     */
    public function save(LegalHoldInterface $legalHold): void
    {
        if (!$legalHold instanceof LegalHold) {
            throw new \InvalidArgumentException('Expected App\Entity\LegalHold instance');
        }

        $this->getEntityManager()->persist($legalHold);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes): LegalHoldInterface
    {
        return new LegalHold(
            $attributes['id'] ?? (string) \Symfony\Component\Uid\Ulid::generate(),
            $attributes['tenant_id'],
            $attributes['document_id'],
            $attributes['reason'],
            $attributes['applied_by'],
            $attributes['matter_reference'] ?? null,
            $attributes['expires_at'] ?? null,
            $attributes['metadata'] ?? []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findExpiringBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.expiresAt >= :from')
            ->andWhere('h.expiresAt <= :to')
            ->andWhere('h.releasedAt IS NULL')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.releasedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentIdsWithActiveHolds(): array
    {
        return $this->createQueryBuilder('h')
            ->select('DISTINCT h.documentId')
            ->where('h.releasedAt IS NULL')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
