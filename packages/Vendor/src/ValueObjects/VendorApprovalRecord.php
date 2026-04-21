<?php

declare(strict_types=1);

namespace Nexus\Vendor\ValueObjects;

final readonly class VendorApprovalRecord
{
    private string $approvedByUserId;

    private \DateTimeImmutable $approvedAt;

    private ?string $approvalNote;

    public function __construct(
        string $approvedByUserId,
        \DateTimeImmutable $approvedAt,
        ?string $approvalNote = null,
    ) {
        $normalizedApprovedByUserId = trim($approvedByUserId);

        if ($normalizedApprovedByUserId === '') {
            throw new \InvalidArgumentException('Approved by user ID cannot be empty.');
        }

        $this->approvedByUserId = $normalizedApprovedByUserId;
        $this->approvedAt = $approvedAt;

        $normalizedApprovalNote = $approvalNote === null ? null : trim($approvalNote);
        $this->approvalNote = $normalizedApprovalNote === '' ? null : $normalizedApprovalNote;
    }

    public function getApprovedByUserId(): string
    {
        return $this->approvedByUserId;
    }

    public function getApprovedAt(): \DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getApprovalNote(): ?string
    {
        return $this->approvalNote;
    }
}
