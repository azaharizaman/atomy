<?php

declare(strict_types=1);

namespace Nexus\Treasury\Models;

use Nexus\Treasury\Contracts\TreasuryApprovalInterface;
use Nexus\Treasury\Enums\ApprovalStatus;

final readonly class TreasuryApproval implements TreasuryApprovalInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $transactionType,
        private float $amount,
        private string $currency,
        private string $description,
        private ApprovalStatus $status,
        private string $submittedBy,
        private \DateTimeImmutable $submittedAt,
        private ?string $approvedBy,
        private ?\DateTimeImmutable $approvedAt,
        private ?string $rejectedBy,
        private ?\DateTimeImmutable $rejectedAt,
        private ?string $rejectionReason,
        private ?string $comments,
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

    public function getTransactionType(): string
    {
        return $this->transactionType;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): ApprovalStatus
    {
        return $this->status;
    }

    public function getSubmittedBy(): string
    {
        return $this->submittedBy;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getRejectedBy(): ?string
    {
        return $this->rejectedBy;
    }

    public function getRejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function withStatus(ApprovalStatus $status): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->transactionType,
            $this->amount,
            $this->currency,
            $this->description,
            $status,
            $this->submittedBy,
            $this->submittedAt,
            $this->approvedBy,
            $this->approvedAt,
            $this->rejectedBy,
            $this->rejectedAt,
            $this->rejectionReason,
            $this->comments,
        );
    }
}
