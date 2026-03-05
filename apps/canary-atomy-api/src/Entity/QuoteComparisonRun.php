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
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'string', length: 36)]
    private string $tenantId;

    #[ORM\Column(name: 'rfq_id', type: 'string', length: 64)]
    private string $rfqId;

    #[ORM\Column(name: 'idempotency_key', type: 'string', length: 128, nullable: true)]
    private ?string $idempotencyKey = null;

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

    #[ORM\Column(type: 'string', length: 32)]
    private string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @param array<string, mixed> $requestPayload
     * @param array<string, mixed> $matrixPayload
     * @param array<string, mixed> $scoringPayload
     * @param array<string, mixed> $approvalPayload
     * @param array<string, mixed> $responsePayload
     */
    public function __construct(
        string $tenantId,
        string $rfqId,
        ?string $idempotencyKey,
        array $requestPayload,
        array $matrixPayload,
        array $scoringPayload,
        array $approvalPayload,
        array $responsePayload,
        string $status
    ) {
        $this->id = (new Ulid())->toBase32();
        $this->tenantId = $tenantId;
        $this->rfqId = $rfqId;
        $this->idempotencyKey = $idempotencyKey;
        $this->requestPayload = $requestPayload;
        $this->matrixPayload = $matrixPayload;
        $this->scoringPayload = $scoringPayload;
        $this->approvalPayload = $approvalPayload;
        $this->responsePayload = $responsePayload;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
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

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
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
}
