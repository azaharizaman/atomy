<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Document;
use App\Entity\DocumentVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Document\Contracts\DocumentVersionInterface;
use Nexus\Document\Contracts\DocumentVersionRepositoryInterface;

/**
 * @extends ServiceEntityRepository<DocumentVersion>
 */
class DocumentVersionRepository extends ServiceEntityRepository implements DocumentVersionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentVersion::class);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?DocumentVersionInterface
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByDocumentId(string $documentId): array
    {
        return $this->findBy(['document' => $documentId], ['versionNumber' => 'DESC']);
    }

    /**
     * {@inheritdoc}
     */
    public function findByVersion(string $documentId, int $versionNumber): ?DocumentVersionInterface
    {
        return $this->findOneBy([
            'document' => $documentId,
            'versionNumber' => $versionNumber,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function save(DocumentVersionInterface $version): void
    {
        if (!$version instanceof DocumentVersion) {
            throw new \InvalidArgumentException('Expected App\Entity\DocumentVersion instance');
        }

        $this->getEntityManager()->persist($version);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $id): void
    {
        $version = $this->find($id);
        if ($version) {
            $this->getEntityManager()->remove($version);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes): DocumentVersionInterface
    {
        $document = $this->getEntityManager()->getReference(Document::class, $attributes['document_id']);

        return new DocumentVersion(
            $attributes['id'] ?? (string) \Symfony\Component\Uid\Ulid::generate(),
            $document,
            $attributes['version_number'],
            $attributes['storage_path'],
            $attributes['checksum'],
            $attributes['file_size'],
            $attributes['created_by'],
            $attributes['change_description'] ?? null
        );
    }
}
