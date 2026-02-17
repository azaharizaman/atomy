<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\ValueObjects\DocumentState;
use Nexus\Document\ValueObjects\DocumentType;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'documents')]
class Document implements DocumentInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 26)]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 50, enumType: DocumentType::class)]
    private DocumentType $type;

    #[ORM\Column(type: 'string', length: 50, enumType: DocumentState::class)]
    private DocumentState $state;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $storagePath;

    #[ORM\Column(type: 'string', length: 64)]
    private string $checksum;

    #[ORM\Column(type: 'string', length: 100)]
    private string $mimeType;

    #[ORM\Column(type: 'bigint')]
    private int $fileSize;

    #[ORM\Column(type: 'string', length: 255)]
    private string $originalFilename;

    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $id,
        string $tenantId,
        string $ownerId,
        DocumentType $type,
        DocumentState $state,
        string $storagePath,
        string $checksum,
        string $mimeType,
        int $fileSize,
        string $originalFilename,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->ownerId = $ownerId;
        $this->type = $type;
        $this->state = $state;
        $this->storagePath = $storagePath;
        $this->checksum = $checksum;
        $this->mimeType = $mimeType;
        $this->fileSize = $fileSize;
        $this->originalFilename = $originalFilename;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getType(): DocumentType
    {
        return $this->type;
    }

    public function getState(): DocumentState
    {
        return $this->state;
    }

    public function setState(DocumentState $state): void
    {
        $this->state = $state;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $path): void
    {
        $this->storagePath = $path;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getFileSize(): int
    {
        return (int) $this->fileSize;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'owner_id' => $this->ownerId,
            'type' => $this->type->value,
            'state' => $this->state->value,
            'storage_path' => $this->storagePath,
            'checksum' => $this->checksum,
            'mime_type' => $this->mimeType,
            'file_size' => $this->getFileSize(),
            'original_filename' => $this->originalFilename,
            'version' => $this->version,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(\DateTimeInterface::ATOM),
            'deleted_at' => $this->deletedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
