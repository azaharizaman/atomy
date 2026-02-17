<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for batch payment run operations.
 */
final readonly class PaymentRunResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $paymentRunId Created payment run ID
     * @param int|null $totalPayments Number of payments included
     * @param int|null $totalAmountCents Total amount of the run in cents
     * @param string|null $status Current status (draft, approved, executed)
     * @param string|null $message Human-readable result message
     */
    public function __construct(
        public bool $success,
        public ?string $paymentRunId = null,
        public ?int $totalPayments = null,
        public ?int $totalAmountCents = null,
        public ?string $status = null,
        public ?string $message = null
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $paymentRunId,
        int $totalPayments,
        int $totalAmountCents,
        string $status,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            paymentRunId: $paymentRunId,
            totalPayments: $totalPayments,
            totalAmountCents: $totalAmountCents,
            status: $status,
            message: $message
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(string $message): self
    {
        return new self(
            success: false,
            message: $message
        );
    }
}
