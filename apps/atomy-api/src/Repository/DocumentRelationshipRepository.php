<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DocumentRelationship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Document\Contracts\DocumentRelationshipInterface;
use Nexus\Document\Contracts\DocumentRelationshipRepositoryInterface;
use Nexus\Document\ValueObjects\RelationshipType;

/**
 * @extends ServiceEntityRepository<DocumentRelationship>
 */
class DocumentRelationshipRepository extends ServiceEntityRepository implements DocumentRelationshipRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentRelationship::class);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?DocumentRelationshipInterface
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySourceDocument(string $documentId, ?RelationshipType $type = null): array
    {
        $criteria = ['sourceDocumentId' => $documentId];
        if ($type) {
            $criteria['relationshipType'] = $type;
        }

        return $this->findBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function findByTargetDocument(string $documentId, ?RelationshipType $type = null): array
    {
        $criteria = ['targetDocumentId' => $documentId];
        if ($type) {
            $criteria['relationshipType'] = $type;
        }

        return $this->findBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $sourceId, string $targetId, RelationshipType $type): bool
    {
        return $this->count([
            'sourceDocumentId' => $sourceId,
            'targetDocumentId' => $targetId,
            'relationshipType' => $type,
        ]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function save(DocumentRelationshipInterface $relationship): void
    {
        if (!$relationship instanceof DocumentRelationship) {
            throw new \InvalidArgumentException('Expected App\Entity\DocumentRelationship instance');
        }

        $this->getEntityManager()->persist($relationship);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $id): void
    {
        $relationship = $this->find($id);
        if ($relationship) {
            $this->getEntityManager()->remove($relationship);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $sourceId, string $targetId, RelationshipType $type, string $createdBy): DocumentRelationshipInterface
    {
        return new DocumentRelationship(
            (string) \Symfony\Component\Uid\Ulid::generate(),
            $sourceId,
            $targetId,
            $type,
            $createdBy
        );
    }
}
