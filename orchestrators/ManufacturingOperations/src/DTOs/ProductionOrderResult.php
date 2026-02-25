<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

/**
 * Result DTO for production order operations.
 * 
 * Contains the result of production order operations including
 * success status, order details, and any errors.
 * 
 * Target User Group: Production Managers
 */
readonly class ProductionOrderResult
{
    public function __construct(
        public bool $success,
        public ?string $orderId = null,
        public ?string $status = null,
        public ?string $message = null,
        public ?array $errors = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $orderId,
        string $status,
        ?string $message = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        ?array $metadata = null
    ): self {
        return new self(
            success: true,
            orderId: $orderId,
            status: $status,
            message: $message,
            createdAt: $createdAt ?? new \DateTimeImmutable(),
            updatedAt: $updatedAt,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(string $message, ?array $errors = null): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors,
        );
    }
}
