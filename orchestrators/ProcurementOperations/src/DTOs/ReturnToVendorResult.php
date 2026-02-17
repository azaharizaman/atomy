<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for return to vendor operations.
 */
final readonly class ReturnToVendorResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $returnId Created return ID
     * @param string|null $returnNumber Human-readable return number
     * @param string|null $status Current return status
     * @param string|null $message Human-readable result message
     * @param array<string, mixed>|null $issues Validation issues or errors
     */
    public function __construct(
        public bool $success,
        public ?string $returnId = null,
        public ?string $returnNumber = null,
        public ?string $status = null,
        public ?string $message = null,
        public ?array $issues = null
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $returnId,
        string $returnNumber,
        string $status,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            returnId: $returnId,
            returnNumber: $returnNumber,
            status: $status,
            message: $message
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(string $message, ?array $issues = null): self
    {
        return new self(
            success: false,
            message: $message,
            issues: $issues
        );
    }
}
