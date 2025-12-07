<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for goods receipt operations.
 */
final readonly class GoodsReceiptResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $goodsReceiptId Created/updated GR ID
     * @param string|null $goodsReceiptNumber Human-readable GR number
     * @param string|null $status Current GR status
     * @param string|null $message Human-readable result message
     * @param bool|null $isPartialReceipt Whether this is a partial receipt
     * @param bool|null $poFullyReceived Whether the PO is now fully received
     * @param int|null $receivedValueCents Total value of received goods in cents
     * @param string|null $accrualJournalEntryId GR-IR accrual journal entry ID
     * @param array<string, array{
     *     quantityOrdered: float,
     *     quantityReceived: float,
     *     quantityOutstanding: float
     * }>|null $lineStatus Per-line receipt status
     * @param array<string, mixed>|null $issues Validation issues or errors
     */
    public function __construct(
        public bool $success,
        public ?string $goodsReceiptId = null,
        public ?string $goodsReceiptNumber = null,
        public ?string $status = null,
        public ?string $message = null,
        public ?bool $isPartialReceipt = null,
        public ?bool $poFullyReceived = null,
        public ?int $receivedValueCents = null,
        public ?string $accrualJournalEntryId = null,
        public ?array $lineStatus = null,
        public ?array $issues = null,
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $goodsReceiptId,
        string $goodsReceiptNumber,
        string $status,
        bool $isPartialReceipt,
        bool $poFullyReceived,
        int $receivedValueCents,
        ?string $accrualJournalEntryId = null,
        ?array $lineStatus = null,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            goodsReceiptId: $goodsReceiptId,
            goodsReceiptNumber: $goodsReceiptNumber,
            status: $status,
            message: $message,
            isPartialReceipt: $isPartialReceipt,
            poFullyReceived: $poFullyReceived,
            receivedValueCents: $receivedValueCents,
            accrualJournalEntryId: $accrualJournalEntryId,
            lineStatus: $lineStatus,
        );
    }

    /**
     * Create a failure result.
     *
     * @param array<string, mixed>|null $issues
     */
    public static function failure(string $message, ?array $issues = null): self
    {
        return new self(
            success: false,
            message: $message,
            issues: $issues,
        );
    }
}
