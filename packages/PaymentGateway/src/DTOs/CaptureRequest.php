<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\DTOs;

use Nexus\Common\ValueObjects\Money;

/**
 * Request to capture an authorized payment.
 */
final readonly class CaptureRequest
{
    /**
     * @param string $authorizationId Authorization ID from authorize response
     * @param Money|null $amount Amount to capture (null = full authorized amount)
     * @param string|null $description Capture description
     * @param array<string, mixed> $metadata Additional metadata
     * @param string|null $idempotencyKey Idempotency key for safe retries
     */
    public function __construct(
        public string $authorizationId,
        public ?Money $amount = null,
        public ?string $description = null,
        public array $metadata = [],
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * Create for full capture.
     */
    public static function full(string $authorizationId, array $metadata = []): self
    {
        return new self(
            authorizationId: $authorizationId,
            amount: null,
            metadata: $metadata,
        );
    }

    /**
     * Create for partial capture.
     */
    public static function partial(
        string $authorizationId,
        Money $amount,
        array $metadata = [],
    ): self {
        return new self(
            authorizationId: $authorizationId,
            amount: $amount,
            metadata: $metadata,
        );
    }

    /**
     * Check if this is a partial capture.
     */
    public function isPartialCapture(): bool
    {
        return $this->amount !== null;
    }

    /**
     * Get amount in minor units (if specified).
     */
    public function getAmountInMinorUnits(): ?int
    {
        return $this->amount?->getAmountInMinorUnits();
    }
}
