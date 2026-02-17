<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DocumentRelationshipRepository;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Document\Contracts\DocumentRelationshipInterface;
use Nexus\Document\ValueObjects\RelationshipType;

#[ORM\Entity(repositoryClass: DocumentRelationshipRepository::class)]
#[ORM\Table(name: 'document_relationships')]
#[ORM\UniqueConstraint(name: 'uniq_doc_rel_type', columns: ['source_document_id', 'target_document_id', 'relationship_type'])]
class DocumentRelationship implements DocumentRelationshipInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $sourceDocumentId;

    #[ORM\Column(type: 'string', length: 26)]
    private string $targetDocumentId;

    #[ORM\Column(type: 'string', length: 50, enumType: RelationshipType::class)]
    private RelationshipType $relationshipType;

    #[ORM\Column(type: 'string', length: 26)]
    private string $createdBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $sourceDocumentId,
        string $targetDocumentId,
        RelationshipType $type,
        string $createdBy
    ) {
        $this->id = $id;
        $this->sourceDocumentId = $sourceDocumentId;
        $this->targetDocumentId = $targetDocumentId;
        $this->relationshipType = $type;
        $this->createdBy = $createdBy;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSourceDocumentId(): string
    {
        return $this->sourceDocumentId;
    }

    public function getTargetDocumentId(): string
    {
        return $this->targetDocumentId;
    }

    public function getRelationshipType(): RelationshipType
    {
        return $this->relationshipType;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source_document_id' => $this->sourceDocumentId,
            'target_document_id' => $this->targetDocumentId,
            'relationship_type' => $this->relationshipType->value,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
