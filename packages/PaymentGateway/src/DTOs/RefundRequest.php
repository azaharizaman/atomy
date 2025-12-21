<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\RefundType;

/**
 * Request to refund a captured payment.
 */
final readonly class RefundRequest
{
    /**
     * @param string $transactionId Original transaction/capture ID
     * @param RefundType $type Type of refund (full/partial)
     * @param Money|null $amount Amount to refund (null = full refund)
     * @param string|null $reason Reason for refund
     * @param array<string, mixed> $metadata Additional metadata
     * @param string|null $idempotencyKey Idempotency key for safe retries
     */
    public function __construct(
        public string $transactionId,
        public RefundType $type = RefundType::FULL,
        public ?Money $amount = null,
        public ?string $reason = null,
        public array $metadata = [],
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * Create for full refund.
     */
    public static function full(string $transactionId, ?string $reason = null): self
    {
        return new self(
            transactionId: $transactionId,
            type: RefundType::FULL,
            amount: null,
            reason: $reason,
        );
    }

    /**
     * Create for partial refund.
     */
    public static function partial(
        string $transactionId,
        Money $amount,
        ?string $reason = null,
    ): self {
        return new self(
            transactionId: $transactionId,
            type: RefundType::PARTIAL,
            amount: $amount,
            reason: $reason,
        );
    }

    /**
     * Check if this is a full refund.
     */
    public function isFullRefund(): bool
    {
        return $this->type === RefundType::FULL;
    }

    /**
     * Get amount in minor units (if specified).
     */
    public function getAmountInMinorUnits(): ?int
    {
        return $this->amount?->getAmountInMinorUnits();
    }
}
