<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\MemoReason;
use Nexus\ProcurementOperations\Enums\MemoType;

/**
 * Request DTO for creating a credit or debit memo.
 */
final readonly class MemoRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param MemoType $type Credit or debit memo
     * @param MemoReason $reason Reason for memo
     * @param Money $amount Memo amount
     * @param string $createdBy User creating the memo
     * @param string|null $invoiceId Optional reference invoice
     * @param string|null $purchaseOrderId Optional reference PO
     * @param string|null $description Description/notes
     * @param \DateTimeImmutable|null $memoDate Memo date (defaults to now)
     * @param array<MemoLineItem> $lineItems Line items for detailed memos
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public MemoType $type,
        public MemoReason $reason,
        public Money $amount,
        public string $createdBy,
        public ?string $invoiceId = null,
        public ?string $purchaseOrderId = null,
        public ?string $description = null,
        public ?\DateTimeImmutable $memoDate = null,
        public array $lineItems = [],
        public array $metadata = [],
    ) {}

    /**
     * Check if memo requires approval based on reason.
     */
    public function requiresApproval(): bool
    {
        return $this->reason->requiresApproval();
    }

    /**
     * Check if this is a credit memo.
     */
    public function isCreditMemo(): bool
    {
        return $this->type === MemoType::CREDIT;
    }

    /**
     * Check if this is a debit memo.
     */
    public function isDebitMemo(): bool
    {
        return $this->type === MemoType::DEBIT;
    }

    /**
     * Get the effective memo date.
     */
    public function getEffectiveDate(): \DateTimeImmutable
    {
        return $this->memoDate ?? new \DateTimeImmutable();
    }
}
