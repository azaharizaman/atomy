<?php

declare(strict_types=1);

namespace Nexus\Vendor\ValueObjects;

use Nexus\Vendor\Internal\BoundedStringValidator;

final readonly class VendorApprovalRecord
{
    private const APPROVED_BY_USER_ID_MAX_LENGTH = 255;
    private const APPROVAL_NOTE_MAX_LENGTH = 5000;

    private string $approvedByUserId;

    private \DateTimeImmutable $approvedAt;

    private ?string $approvalNote;

    public function __construct(
        string $approvedByUserId,
        \DateTimeImmutable $approvedAt,
        ?string $approvalNote = null,
    ) {
        $this->approvedByUserId = BoundedStringValidator::requireTrimmedNonEmpty(
            $approvedByUserId,
            self::APPROVED_BY_USER_ID_MAX_LENGTH,
            'Approved by user ID cannot be empty.',
            'Approved by user ID exceeds maximum length.',
        );
        $this->approvedAt = $approvedAt;
        $this->approvalNote = BoundedStringValidator::nullableTrimmed(
            $approvalNote,
            self::APPROVAL_NOTE_MAX_LENGTH,
            'Approval note exceeds maximum length.',
        );
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
