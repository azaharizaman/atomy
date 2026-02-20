<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Enums\ApprovalStatus;

interface TreasuryApprovalInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getTransactionType(): string;

    public function getTransactionId(): string;

    public function getTransactionReference(): ?string;

    public function getAmount(): Money;

    public function getDescription(): ?string;

    public function getStatus(): ApprovalStatus;

    public function getRequestedBy(): string;

    public function getRequestedAt(): DateTimeImmutable;

    public function getApprovers(): array;

    public function getApprovedBy(): ?string;

    public function getApprovedAt(): ?DateTimeImmutable;

    public function getRejectedBy(): ?string;

    public function getRejectedAt(): ?DateTimeImmutable;

    public function getRejectionReason(): ?string;

    public function getApprovalNotes(): ?string;

    public function getExpiresAt(): ?DateTimeImmutable;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function isPending(): bool;

    public function isExpired(): bool;

    public function isApproved(): bool;

    public function isRejected(): bool;
}
