<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DisposalCertificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Document\Contracts\DisposalCertificationInterface;

#[ORM\Entity(repositoryClass: DisposalCertificationRepository::class)]
#[ORM\Table(name: 'disposal_certifications')]
class DisposalCertification implements DisposalCertificationInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 26)]
    private string $documentId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $documentType;

    #[ORM\Column(type: 'string', length: 255)]
    private string $documentName;

    #[ORM\Column(type: 'string', length: 50)]
    private string $disposalMethod;

    #[ORM\Column(type: 'string', length: 26)]
    private string $disposedBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $disposedAt;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $approvedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(type: 'text')]
    private string $disposalReason;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $documentCreatedAt;

    #[ORM\Column(type: 'integer')]
    private int $retentionPeriodDays;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $retentionExpiredAt;

    #[ORM\Column(type: 'boolean')]
    private bool $legalHoldVerified;

    #[ORM\Column(type: 'string', length: 64)]
    private string $documentChecksum;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $regulatoryBasis = null;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $witnessedBy = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'json')]
    private array $chainOfCustody = [];

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->tenantId = $data['tenant_id'];
        $this->documentId = $data['document_id'];
        $this->documentType = $data['document_type'];
        $this->documentName = $data['document_name'];
        $this->disposalMethod = $data['disposal_method'];
        $this->disposedBy = $data['disposed_by'];
        $this->disposedAt = $data['disposed_at'] instanceof \DateTimeImmutable ? $data['disposed_at'] : new \DateTimeImmutable($data['disposed_at']);
        $this->approvedBy = $data['approved_by'] ?? null;
        $this->approvedAt = isset($data['approved_at']) ? ($data['approved_at'] instanceof \DateTimeImmutable ? $data['approved_at'] : new \DateTimeImmutable($data['approved_at'])) : null;
        $this->disposalReason = $data['disposal_reason'];
        $this->documentCreatedAt = $data['document_created_at'] instanceof \DateTimeImmutable ? $data['document_created_at'] : new \DateTimeImmutable($data['document_created_at']);
        $this->retentionPeriodDays = $data['retention_period_days'];
        $this->retentionExpiredAt = $data['retention_expired_at'] instanceof \DateTimeImmutable ? $data['retention_expired_at'] : new \DateTimeImmutable($data['retention_expired_at']);
        $this->legalHoldVerified = $data['legal_hold_verified'] ?? false;
        $this->documentChecksum = $data['document_checksum'];
        $this->regulatoryBasis = $data['regulatory_basis'] ?? null;
        $this->witnessedBy = $data['witnessed_by'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
        $this->chainOfCustody = $data['chain_of_custody'] ?? [];
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

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function getDocumentName(): string
    {
        return $this->documentName;
    }

    public function getDisposalMethod(): string
    {
        return $this->disposalMethod;
    }

    public function getDisposedBy(): string
    {
        return $this->disposedBy;
    }

    public function getDisposedAt(): \DateTimeInterface
    {
        return $this->disposedAt;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function getDisposalReason(): string
    {
        return $this->disposalReason;
    }

    public function getDocumentCreatedAt(): \DateTimeInterface
    {
        return $this->documentCreatedAt;
    }

    public function getRetentionPeriodDays(): int
    {
        return $this->retentionPeriodDays;
    }

    public function getRetentionExpiredAt(): \DateTimeInterface
    {
        return $this->retentionExpiredAt;
    }

    public function isLegalHoldVerified(): bool
    {
        return $this->legalHoldVerified;
    }

    public function getDocumentChecksum(): string
    {
        return $this->documentChecksum;
    }

    public function getRegulatoryBasis(): ?string
    {
        return $this->regulatoryBasis;
    }

    public function getWitnessedBy(): ?string
    {
        return $this->witnessedBy;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getChainOfCustody(): array
    {
        return $this->chainOfCustody;
    }

    public function toComplianceReport(): array
    {
        return [
            'certification_id' => $this->id,
            'document_id' => $this->documentId,
            'document_name' => $this->documentName,
            'document_type' => $this->documentType,
            'retention_days' => $this->retentionPeriodDays,
            'retention_compliant' => $this->disposedAt >= $this->retentionExpiredAt,
            'legal_hold_clear' => $this->legalHoldVerified,
            'disposed_by' => $this->disposedBy,
            'disposed_at' => $this->disposedAt->format(\DateTimeInterface::ATOM),
            'disposal_method' => $this->disposalMethod,
            'regulatory_basis' => $this->regulatoryBasis,
            'has_witness' => $this->witnessedBy !== null,
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'document_id' => $this->documentId,
            'document_type' => $this->documentType,
            'document_name' => $this->documentName,
            'disposal_method' => $this->disposalMethod,
            'disposed_by' => $this->disposedBy,
            'disposed_at' => $this->disposedAt->format(\DateTimeInterface::ATOM),
            'approved_by' => $this->approvedBy,
            'approved_at' => $this->approvedAt?->format(\DateTimeInterface::ATOM),
            'disposal_reason' => $this->disposalReason,
            'document_created_at' => $this->documentCreatedAt->format(\DateTimeInterface::ATOM),
            'retention_period_days' => $this->retentionPeriodDays,
            'retention_expired_at' => $this->retentionExpiredAt->format(\DateTimeInterface::ATOM),
            'legal_hold_verified' => $this->legalHoldVerified,
            'document_checksum' => $this->documentChecksum,
            'regulatory_basis' => $this->regulatoryBasis,
            'witnessed_by' => $this->witnessedBy,
            'metadata' => $this->metadata,
            'chain_of_custody' => $this->chainOfCustody,
        ];
    }
}
