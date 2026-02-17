<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DisposalCertification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Document\Contracts\DisposalCertificationInterface;
use Nexus\Document\Contracts\DisposalCertificationRepositoryInterface;

/**
 * @extends ServiceEntityRepository<DisposalCertification>
 */
class DisposalCertificationRepository extends ServiceEntityRepository implements DisposalCertificationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DisposalCertification::class);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?DisposalCertificationInterface
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByDocumentId(string $documentId): ?DisposalCertificationInterface
    {
        return $this->findOneBy(['documentId' => $documentId]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.disposedAt >= :from')
            ->andWhere('c.disposedAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function save(DisposalCertificationInterface $certification): void
    {
        if (!$certification instanceof DisposalCertification) {
            throw new \InvalidArgumentException('Expected App\Entity\DisposalCertification instance');
        }

        $this->getEntityManager()->persist($certification);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): DisposalCertificationInterface
    {
        return new DisposalCertification($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatistics(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.disposedAt >= :from')
            ->andWhere('c.disposedAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $results = $qb->getQuery()->getResult();

        $stats = [
            'total_disposed' => count($results),
            'compliant_count' => 0,
            'by_method' => [],
            'by_type' => [],
        ];

        foreach ($results as $cert) {
            if ($cert->getDisposedAt() >= $cert->getRetentionExpiredAt()) {
                $stats['compliant_count']++;
            }

            $method = $cert->getDisposalMethod();
            $stats['by_method'][$method] = ($stats['by_method'][$method] ?? 0) + 1;

            $type = $cert->getDocumentType();
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
        }

        return $stats;
    }
}
