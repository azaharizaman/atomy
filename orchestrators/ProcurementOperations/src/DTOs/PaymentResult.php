<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for payment processing operations.
 */
final readonly class PaymentResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $paymentId Payment ID
     * @param string|null $paymentReference Payment reference number
     * @param string|null $paymentBatchId Payment batch ID (if batched)
     * @param string|null $status Payment status (scheduled, executed, failed, voided)
     * @param string|null $message Human-readable result message
     * @param int|null $totalAmountCents Total payment amount in cents
     * @param int|null $discountTakenCents Early payment discount taken in cents
     * @param int|null $netAmountCents Net amount paid (total - discount) in cents
     * @param array<string>|null $paidInvoiceIds List of paid invoice IDs
     * @param string|null $journalEntryId Payment journal entry ID
     * @param \DateTimeImmutable|null $scheduledDate Scheduled payment date
     * @param \DateTimeImmutable|null $executedAt When payment was executed
     * @param string|null $failureReason Failure reason (if failed)
     * @param array<string, mixed>|null $issues Validation issues or errors
     */
    public function __construct(
        public bool $success,
        public ?string $paymentId = null,
        public ?string $paymentReference = null,
        public ?string $paymentBatchId = null,
        public ?string $status = null,
        public ?string $message = null,
        public ?int $totalAmountCents = null,
        public ?int $discountTakenCents = null,
        public ?int $netAmountCents = null,
        public ?array $paidInvoiceIds = null,
        public ?string $journalEntryId = null,
        public ?\DateTimeImmutable $scheduledDate = null,
        public ?\DateTimeImmutable $executedAt = null,
        public ?string $failureReason = null,
        public ?array $issues = null,
    ) {}

    /**
     * Create a scheduled payment result.
     *
     * @param array<string> $paidInvoiceIds
     */
    public static function scheduled(
        string $paymentId,
        string $paymentReference,
        \DateTimeImmutable $scheduledDate,
        int $totalAmountCents,
        array $paidInvoiceIds,
        ?string $paymentBatchId = null,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            paymentId: $paymentId,
            paymentReference: $paymentReference,
            paymentBatchId: $paymentBatchId,
            status: 'scheduled',
            message: $message ?? 'Payment scheduled successfully',
            totalAmountCents: $totalAmountCents,
            paidInvoiceIds: $paidInvoiceIds,
            scheduledDate: $scheduledDate,
        );
    }

    /**
     * Create an executed payment result.
     *
     * @param array<string> $paidInvoiceIds
     */
    public static function executed(
        string $paymentId,
        string $paymentReference,
        int $totalAmountCents,
        int $discountTakenCents,
        int $netAmountCents,
        array $paidInvoiceIds,
        string $journalEntryId,
        \DateTimeImmutable $executedAt,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            paymentId: $paymentId,
            paymentReference: $paymentReference,
            status: 'executed',
            message: $message ?? 'Payment executed successfully',
            totalAmountCents: $totalAmountCents,
            discountTakenCents: $discountTakenCents,
            netAmountCents: $netAmountCents,
            paidInvoiceIds: $paidInvoiceIds,
            journalEntryId: $journalEntryId,
            executedAt: $executedAt,
        );
    }

    /**
     * Create a failure result.
     *
     * @param array<string, mixed>|null $issues
     */
    public static function failure(string $message, ?string $failureReason = null, ?array $issues = null): self
    {
        return new self(
            success: false,
            status: 'failed',
            message: $message,
            failureReason: $failureReason,
            issues: $issues,
        );
    }
}
