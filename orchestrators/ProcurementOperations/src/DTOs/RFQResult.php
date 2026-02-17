<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for RFQ operations.
 */
final readonly class RFQResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $rfqId Created/updated RFQ ID
     * @param string|null $rfqNumber Human-readable RFQ number
     * @param string|null $status Current RFQ status
     * @param string|null $message Human-readable result message
     */
    public function __construct(
        public bool $success,
        public ?string $rfqId = null,
        public ?string $rfqNumber = null,
        public ?string $status = null,
        public ?string $message = null
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $rfqId,
        string $rfqNumber,
        string $status,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            rfqId: $rfqId,
            rfqNumber: $rfqNumber,
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
