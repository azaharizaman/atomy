<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\ValueObjects\DocumentType;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository implements DocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?DocumentInterface
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByOwner(string $ownerId): array
    {
        return $this->findBy(['ownerId' => $ownerId]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByType(DocumentType $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByTags(array $tags): array
    {
        // This is a simplified implementation of tag searching in JSON
        $qb = $this->createQueryBuilder('d');
        
        foreach ($tags as $index => $tag) {
            $qb->andWhere(sprintf('JSON_CONTAINS(d.metadata, :tag_%d, "$.tags") = 1', $index))
               ->setParameter(sprintf('tag_%d', $index), json_encode($tag));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.createdAt >= :from')
            ->andWhere('d.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleted(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.deletedAt IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findEligibleForDisposal(\DateTimeInterface $cutoffDate, DocumentType $type): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.createdAt <= :cutoff')
            ->andWhere('d.type = :type')
            ->andWhere('d.deletedAt IS NOT NULL') // Only soft-deleted docs are eligible for purge
            ->setParameter('cutoff', $cutoffDate)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function save(DocumentInterface $document): void
    {
        if (!$document instanceof Document) {
            throw new \InvalidArgumentException('Expected App\Entity\Document instance');
        }

        $this->getEntityManager()->persist($document);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $id): void
    {
        $document = $this->find($id);
        if ($document) {
            $document->setDeletedAt(new \DateTimeImmutable());
            $this->save($document);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forceDelete(string $id): void
    {
        $document = $this->find($id);
        if ($document) {
            $this->getEntityManager()->remove($document);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes): DocumentInterface
    {
        return new Document(
            $attributes['id'],
            $attributes['tenant_id'],
            $attributes['owner_id'],
            $attributes['type'],
            $attributes['state'],
            $attributes['storage_path'],
            $attributes['checksum'],
            $attributes['mime_type'],
            $attributes['file_size'],
            $attributes['original_filename'],
            $attributes['metadata'] ?? []
        );
    }
}
