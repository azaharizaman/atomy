<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DocumentVersionRepository;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Document\Contracts\DocumentVersionInterface;

#[ORM\Entity(repositoryClass: DocumentVersionRepository::class)]
#[ORM\Table(name: 'document_versions')]
class DocumentVersion implements DocumentVersionInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Document $document;

    #[ORM\Column(type: 'integer')]
    private int $versionNumber;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $storagePath;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $changeDescription = null;

    #[ORM\Column(type: 'string', length: 26)]
    private string $createdBy;

    #[ORM\Column(type: 'string', length: 64)]
    private string $checksum;

    #[ORM\Column(type: 'bigint')]
    private int $fileSize;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        Document $document,
        int $versionNumber,
        string $storagePath,
        string $checksum,
        int $fileSize,
        string $createdBy,
        ?string $changeDescription = null
    ) {
        $this->id = $id;
        $this->document = $document;
        $this->versionNumber = $versionNumber;
        $this->storagePath = $storagePath;
        $this->checksum = $checksum;
        $this->fileSize = $fileSize;
        $this->createdBy = $createdBy;
        $this->changeDescription = $changeDescription;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDocumentId(): string
    {
        return $this->document->getId();
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function getChangeDescription(): ?string
    {
        return $this->changeDescription;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getFileSize(): int
    {
        return (int) $this->fileSize;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->getDocumentId(),
            'version_number' => $this->versionNumber,
            'storage_path' => $this->storagePath,
            'change_description' => $this->changeDescription,
            'created_by' => $this->createdBy,
            'checksum' => $this->checksum,
            'file_size' => $this->getFileSize(),
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
