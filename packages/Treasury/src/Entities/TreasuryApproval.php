<?php

declare(strict_types=1);

namespace Nexus\Treasury\Entities;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\TreasuryApprovalInterface;
use Nexus\Treasury\Enums\ApprovalStatus;

final readonly class TreasuryApproval implements TreasuryApprovalInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $transactionType,
        private string $transactionId,
        private ?string $transactionReference,
        private Money $amount,
        private ?string $description,
        private ApprovalStatus $status,
        private string $requestedBy,
        private DateTimeImmutable $requestedAt,
        private array $approvers,
        private ?string $approvedBy,
        private ?DateTimeImmutable $approvedAt,
        private ?string $rejectedBy,
        private ?DateTimeImmutable $rejectedAt,
        private ?string $rejectionReason,
        private ?string $approvalNotes,
        private ?DateTimeImmutable $expiresAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

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

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): ApprovalStatus
    {
        return $this->status;
    }

    public function getRequestedBy(): string
    {
        return $this->requestedBy;
    }

    public function getRequestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getApprovers(): array
    {
        return $this->approvers;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function getApprovedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getRejectedBy(): ?string
    {
        return $this->rejectedBy;
    }

    public function getRejectedAt(): ?DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getApprovalNotes(): ?string
    {
        return $this->approvalNotes;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::PENDING;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return new DateTimeImmutable() > $this->expiresAt;
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === ApprovalStatus::REJECTED;
    }
}
