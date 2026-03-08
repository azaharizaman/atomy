<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuoteComparisonRunRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: QuoteComparisonRunRepository::class)]
#[ORM\Table(name: 'quote_comparison_runs')]
#[ORM\UniqueConstraint(name: 'UNIQ_QCR_TENANT_RFQ_IDEMPOTENCY', columns: ['tenant_id', 'rfq_id', 'idempotency_key'])]
class QuoteComparisonRun
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_STALE = 'stale';
    public const STATUS_DISCARDED = 'discarded';

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(name: 'tenant_id', type: 'string', length: 36)]
    private string $tenantId;

    #[ORM\Column(name: 'rfq_id', type: 'string', length: 64)]
    private string $rfqId;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'idempotency_key', type: 'string', length: 128, nullable: true)]
    private ?string $idempotencyKey = null;

    #[ORM\Column(name: 'is_preview', type: Types::BOOLEAN)]
    private bool $isPreview;

    #[ORM\Column(name: 'created_by', type: 'string', length: 128, nullable: true)]
    private ?string $createdBy;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $requestPayload;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $matrixPayload;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $scoringPayload;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $approvalPayload;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $responsePayload;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'readiness_payload', type: Types::JSON)]
    private array $readinessPayload;

    #[ORM\Column(type: 'string', length: 32)]
    private string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(name: 'discarded_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $discardedAt = null;

    #[ORM\Column(name: 'discarded_by', type: 'string', length: 128, nullable: true)]
    private ?string $discardedBy = null;

    /**
     * @param array<string, mixed> $requestPayload
     * @param array<string, mixed> $matrixPayload
     * @param array<string, mixed> $scoringPayload
     * @param array<string, mixed> $approvalPayload
     * @param array<string, mixed> $responsePayload
     * @param array<string, mixed> $readinessPayload
     */
    public function __construct(
        string $tenantId,
        string $rfqId,
        string $name,
        ?string $description,
        ?string $idempotencyKey,
        bool $isPreview,
        ?string $createdBy,
        array $requestPayload,
        array $matrixPayload,
        array $scoringPayload,
        array $approvalPayload,
        array $responsePayload,
        array $readinessPayload,
        string $status,
        ?\DateTimeImmutable $expiresAt = null,
    ) {
        $this->id = new Ulid();
        $this->tenantId = $tenantId;
        $this->rfqId = $rfqId;
        $this->name = $name;
        $this->description = $description;
        $this->idempotencyKey = $idempotencyKey;
        $this->isPreview = $isPreview;
        $this->createdBy = $createdBy;
        $this->requestPayload = $requestPayload;
        $this->matrixPayload = $matrixPayload;
        $this->scoringPayload = $scoringPayload;
        $this->approvalPayload = $approvalPayload;
        $this->responsePayload = $responsePayload;
        $this->readinessPayload = $readinessPayload;
        $this->status = $status;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getRfqId(): string
    {
        return $this->rfqId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function isPreview(): bool
    {
        return $this->isPreview;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /** @return array<string, mixed> */
    public function getRequestPayload(): array
    {
        return $this->requestPayload;
    }

    /** @return array<string, mixed> */
    public function getMatrixPayload(): array
    {
        return $this->matrixPayload;
    }

    /** @return array<string, mixed> */
    public function getScoringPayload(): array
    {
        return $this->scoringPayload;
    }

    /** @return array<string, mixed> */
    public function getApprovalPayload(): array
    {
        return $this->approvalPayload;
    }

    /** @return array<string, mixed> */
    public function getResponsePayload(): array
    {
        return $this->responsePayload;
    }

    /** @return array<string, mixed> */
    public function getReadinessPayload(): array
    {
        return $this->readinessPayload;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getDiscardedAt(): ?\DateTimeImmutable
    {
        return $this->discardedAt;
    }

    public function getDiscardedBy(): ?string
    {
        return $this->discardedBy;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param array<string, mixed> $approvalPayload
     */
    public function markDecision(string $status, array $approvalPayload): void
    {
        $this->status = $status;
        $this->approvalPayload = $approvalPayload;
        $this->responsePayload['status'] = $status;
        $this->responsePayload['approval'] = $approvalPayload;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @param array<string, mixed> $responsePayload
     */
    public function markResponsePayload(array $responsePayload): void
    {
        $this->responsePayload = array_merge($this->responsePayload, $responsePayload);
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Promote a draft/preview run to a saved (active) run.
     */
    public function save(string $name, ?string $description, ?string $status, ?\DateTimeImmutable $expiresAt): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->isPreview = false;

        if ($status !== null) {
            $this->status = $status;
        }

        $this->expiresAt = $expiresAt;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Mark the run as discarded (soft-delete with audit trail).
     */
    public function discard(string $discardedBy): void
    {
        $this->status = self::STATUS_DISCARDED;
        $this->discardedAt = new \DateTimeImmutable();
        $this->discardedBy = $discardedBy;
        $this->updatedAt = $this->discardedAt;
    }

    /**
     * Mark the run as stale (invalidated by external change or expiry).
     */
    public function markStale(): void
    {
        $this->status = self::STATUS_STALE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Check if the run has expired based on its expiry date.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return new \DateTimeImmutable() > $this->expiresAt;
    }

    /**
     * Whether the run is in a terminal state and can no longer be acted upon.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_STALE,
            self::STATUS_DISCARDED,
        ], true);
    }
}
