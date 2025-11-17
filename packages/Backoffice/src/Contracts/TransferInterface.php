<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Defines the structure and operations for a Transfer entity.
 *
 * Represents a staff transfer request from one organizational unit to another.
 */
interface TransferInterface
{
    public function getId(): string;

    public function getStaffId(): string;

    public function getFromDepartmentId(): ?string;

    public function getToDepartmentId(): ?string;

    public function getFromOfficeId(): ?string;

    public function getToOfficeId(): ?string;

    public function getTransferType(): string;

    public function getStatus(): string;

    public function getEffectiveDate(): \DateTimeInterface;

    public function getReason(): ?string;

    public function getRequestedBy(): string;

    public function getRequestedAt(): \DateTimeInterface;

    public function getApprovedBy(): ?string;

    public function getApprovedAt(): ?\DateTimeInterface;

    public function getRejectedBy(): ?string;

    public function getRejectedAt(): ?\DateTimeInterface;

    public function getRejectionReason(): ?string;

    public function getCompletedAt(): ?\DateTimeInterface;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    public function getCreatedAt(): \DateTimeInterface;

    public function getUpdatedAt(): \DateTimeInterface;

    public function isPending(): bool;

    public function isApproved(): bool;

    public function isRejected(): bool;

    public function isCompleted(): bool;
}
