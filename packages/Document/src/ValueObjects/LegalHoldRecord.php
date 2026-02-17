<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

use Nexus\Document\Contracts\LegalHoldInterface;

/**
 * Immutable legal hold record.
 */
final readonly class LegalHoldRecord implements LegalHoldInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $documentId,
        private string $reason,
        private string $appliedBy,
        private \DateTimeInterface $appliedAt,
        private ?string $matterReference = null,
        private ?string $releasedBy = null,
        private ?\DateTimeInterface $releasedAt = null,
        private ?string $releaseReason = null,
        private ?\DateTimeInterface $expiresAt = null,
        private array $metadata = []
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getMatterReference(): ?string
    {
        return $this->matterReference;
    }

    public function getAppliedBy(): string
    {
        return $this->appliedBy;
    }

    public function getAppliedAt(): \DateTimeInterface
    {
        return $this->appliedAt;
    }

    public function getReleasedBy(): ?string
    {
        return $this->releasedBy;
    }

    public function getReleasedAt(): ?\DateTimeInterface
    {
        return $this->releasedAt;
    }

    public function getReleaseReason(): ?string
    {
        return $this->releaseReason;
    }

    public function isActive(): bool
    {
        return $this->releasedAt === null;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'document_id' => $this->documentId,
            'reason' => $this->reason,
            'matter_reference' => $this->matterReference,
            'applied_by' => $this->appliedBy,
            'applied_at' => $this->appliedAt->format(\DateTimeInterface::ATOM),
            'released_by' => $this->releasedBy,
            'released_at' => $this->releasedAt?->format(\DateTimeInterface::ATOM),
            'release_reason' => $this->releaseReason,
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
            'is_active' => $this->isActive(),
        ];
    }
}
