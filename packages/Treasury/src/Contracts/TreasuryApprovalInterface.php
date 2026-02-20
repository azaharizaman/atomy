<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use Nexus\Treasury\Enums\ApprovalStatus;

/**
 * Treasury Approval Interface
 */
interface TreasuryApprovalInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getTransactionType(): string;

    public function getAmount(): float;

    public function getCurrency(): string;

    public function getDescription(): string;

    public function getStatus(): ApprovalStatus;

    /**
     * Create a new instance with the given status.
     *
     * @param ApprovalStatus $status The new status
     * @return self
     */
    public function withStatus(ApprovalStatus $status): self;

    public function getSubmittedBy(): string;

    public function getSubmittedAt(): \DateTimeImmutable;

    public function getApprovedBy(): ?string;

    public function getApprovedAt(): ?\DateTimeImmutable;

    public function getRejectedBy(): ?string;

    public function getRejectedAt(): ?\DateTimeImmutable;

    public function getRejectionReason(): ?string;

    public function getComments(): ?string;
}
