<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for invoice matching operations.
 */
final readonly class MatchingResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param bool $matched Whether the invoice was successfully matched
     * @param string|null $vendorBillId Vendor bill ID
     * @param string|null $purchaseOrderId Purchase order ID
     * @param string|null $message Human-readable result message
     * @param string|null $failureReason Reason for match failure (if !matched)
     * @param float|null $priceVariancePercent Price variance percentage
     * @param float|null $quantityVariancePercent Quantity variance percentage
     * @param bool|null $withinTolerance Whether variances are within tolerance
     * @param string|null $accrualReversalJournalEntryId GR-IR reversal journal entry ID
     * @param string|null $payableLiabilityJournalEntryId AP liability journal entry ID
     * @param array<string, array{
     *     type: string,
     *     field: string,
     *     expected: mixed,
     *     actual: mixed,
     *     variancePercent: float
     * }>|null $variances Detailed variance breakdown
     * @param array<string, mixed>|null $issues Validation issues or errors
     */
    public function __construct(
        public bool $success,
        public bool $matched,
        public ?string $vendorBillId = null,
        public ?string $purchaseOrderId = null,
        public ?string $message = null,
        public ?string $failureReason = null,
        public ?float $priceVariancePercent = null,
        public ?float $quantityVariancePercent = null,
        public ?bool $withinTolerance = null,
        public ?string $accrualReversalJournalEntryId = null,
        public ?string $payableLiabilityJournalEntryId = null,
        public ?array $variances = null,
        public ?array $issues = null,
    ) {}

    /**
     * Create a successful match result.
     */
    public static function matched(
        string $vendorBillId,
        string $purchaseOrderId,
        float $priceVariancePercent,
        float $quantityVariancePercent,
        ?string $accrualReversalJournalEntryId = null,
        ?string $payableLiabilityJournalEntryId = null,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            matched: true,
            vendorBillId: $vendorBillId,
            purchaseOrderId: $purchaseOrderId,
            message: $message ?? 'Invoice matched successfully',
            priceVariancePercent: $priceVariancePercent,
            quantityVariancePercent: $quantityVariancePercent,
            withinTolerance: true,
            accrualReversalJournalEntryId: $accrualReversalJournalEntryId,
            payableLiabilityJournalEntryId: $payableLiabilityJournalEntryId,
        );
    }

    /**
     * Create a failed match result.
     *
     * @param array<string, array{
     *     type: string,
     *     field: string,
     *     expected: mixed,
     *     actual: mixed,
     *     variancePercent: float
     * }>|null $variances
     */
    public static function failed(
        string $vendorBillId,
        string $purchaseOrderId,
        string $failureReason,
        float $priceVariancePercent,
        float $quantityVariancePercent,
        ?array $variances = null
    ): self {
        return new self(
            success: true, // Operation completed successfully, but match failed
            matched: false,
            vendorBillId: $vendorBillId,
            purchaseOrderId: $purchaseOrderId,
            message: 'Invoice match failed: ' . $failureReason,
            failureReason: $failureReason,
            priceVariancePercent: $priceVariancePercent,
            quantityVariancePercent: $quantityVariancePercent,
            withinTolerance: false,
            variances: $variances,
        );
    }

    /**
     * Create an error result.
     *
     * @param array<string, mixed>|null $issues
     */
    public static function error(string $message, ?array $issues = null): self
    {
        return new self(
            success: false,
            matched: false,
            message: $message,
            issues: $issues,
        );
    }
}
