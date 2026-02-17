<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LegalHoldRepository;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Document\Contracts\LegalHoldInterface;

#[ORM\Entity(repositoryClass: LegalHoldRepository::class)]
#[ORM\Table(name: 'legal_holds')]
class LegalHold implements LegalHoldInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 26)]
    private string $documentId;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $matterReference = null;

    #[ORM\Column(type: 'string', length: 26)]
    private string $appliedBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $appliedAt;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $releasedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $releaseReason = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    public function __construct(
        string $id,
        string $tenantId,
        string $documentId,
        string $reason,
        string $appliedBy,
        ?string $matterReference = null,
        ?\DateTimeImmutable $expiresAt = null,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->documentId = $documentId;
        $this->reason = $reason;
        $this->appliedBy = $appliedBy;
        $this->matterReference = $matterReference;
        $this->expiresAt = $expiresAt;
        $this->metadata = $metadata;
        $this->appliedAt = new \DateTimeImmutable();
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

    public function setReleasedBy(?string $userId): void
    {
        $this->releasedBy = $userId;
    }

    public function getReleasedAt(): ?\DateTimeInterface
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $date): void
    {
        $this->releasedAt = $date;
    }

    public function getReleaseReason(): ?string
    {
        return $this->releaseReason;
    }

    public function setReleaseReason(?string $reason): void
    {
        $this->releaseReason = $reason;
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
