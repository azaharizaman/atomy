<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Auditable;
use Nexus\Common\Contracts\SerializableVO;

/**
 * Immutable audit metadata value object.
 * 
 * Tracks created/updated by and timestamps for entities.
 */
final readonly class AuditMetadata implements Auditable, SerializableVO
{
    /**
     * @param string $createdBy User ID who created this
     * @param \DateTimeImmutable $createdAt When this was created
     * @param string|null $updatedBy User ID who last updated this
     * @param \DateTimeImmutable|null $updatedAt When this was last updated
     */
    public function __construct(
        private string $createdBy,
        private \DateTimeImmutable $createdAt,
        private ?string $updatedBy = null,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
    }

    /**
     * Create new audit metadata for entity creation.
     */
    public static function forCreate(string $userId): self
    {
        return new self(
            createdBy: $userId,
            createdAt: new \DateTimeImmutable()
        );
    }

    /**
     * Create updated metadata.
     */
    public function withUpdate(string $userId): self
    {
        return new self(
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedBy: $userId,
            updatedAt: new \DateTimeImmutable()
        );
    }

    // Auditable implementation
    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_by' => $this->updatedBy,
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function toString(): string
    {
        $created = "Created by {$this->createdBy} at {$this->createdAt->format('Y-m-d H:i:s')}";
        
        if ($this->updatedBy && $this->updatedAt) {
            $created .= ", Updated by {$this->updatedBy} at {$this->updatedAt->format('Y-m-d H:i:s')}";
        }

        return $created;
    }

    public static function fromArray(array $data): static
    {
        return new self(
            createdBy: $data['created_by'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedBy: $data['updated_by'] ?? null,
            updatedAt: isset($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null
        );
    }
}
