<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

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
        public ?ProductionOrderStatus $status = null,
        public ?string $message = null,
        public ?array $errors = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $orderId,
        ProductionOrderStatus $status,
        ?string $message = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
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
