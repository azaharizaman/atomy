<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for service receipt operations.
 */
final readonly class ServiceReceiptResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $receiptId Created service receipt ID
     * @param string|null $status Current status
     * @param string|null $message Human-readable result message
     * @param int|null $totalAmountCents Total accepted amount in cents
     */
    public function __construct(
        public bool $success,
        public ?string $receiptId = null,
        public ?string $status = null,
        public ?string $message = null,
        public ?int $totalAmountCents = null
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $receiptId,
        string $status,
        int $totalAmountCents,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            receiptId: $receiptId,
            status: $status,
            message: $message,
            totalAmountCents: $totalAmountCents
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
