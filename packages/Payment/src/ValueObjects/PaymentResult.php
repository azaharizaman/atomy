<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment result value object.
 *
 * Represents the outcome of a payment execution attempt.
 */
final readonly class PaymentResult
{
    /**
     * @param bool $success Whether the payment was successful
     * @param string $paymentId The payment transaction ID
     * @param string|null $providerTransactionId External provider's transaction ID
     * @param Money|null $settledAmount The actual settled amount (may differ from requested)
     * @param string|null $failureCode Error code if failed
     * @param string|null $failureMessage Error message if failed
     * @param \DateTimeImmutable $timestamp When the result was received
     * @param array<string, mixed> $metadata Additional result metadata
     */
    public function __construct(
        public bool $success,
        public string $paymentId,
        public ?string $providerTransactionId = null,
        public ?Money $settledAmount = null,
        public ?string $failureCode = null,
        public ?string $failureMessage = null,
        public \DateTimeImmutable $timestamp = new \DateTimeImmutable(),
        public array $metadata = [],
    ) {
    }

    /**
     * Create a successful payment result.
     */
    public static function success(
        string $paymentId,
        Money $settledAmount,
        ?string $providerTransactionId = null,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            paymentId: $paymentId,
            providerTransactionId: $providerTransactionId,
            settledAmount: $settledAmount,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed payment result.
     */
    public static function failure(
        string $paymentId,
        string $failureCode,
        string $failureMessage,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            paymentId: $paymentId,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
            metadata: $metadata,
        );
    }

    /**
     * Create a pending payment result (async processing).
     */
    public static function pending(
        string $paymentId,
        ?string $providerTransactionId = null,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            paymentId: $paymentId,
            providerTransactionId: $providerTransactionId,
            metadata: array_merge($metadata, ['status' => 'pending']),
        );
    }

    /**
     * Check if the payment is successful.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the payment failed.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Check if this is a pending result.
     */
    public function isPending(): bool
    {
        return $this->success && ($this->metadata['status'] ?? null) === 'pending';
    }

    /**
     * Get the settled amount.
     */
    public function getSettledAmount(): ?Money
    {
        return $this->settledAmount;
    }

    /**
     * Get the provider transaction ID.
     */
    public function getProviderTransactionId(): ?string
    {
        return $this->providerTransactionId;
    }

    /**
     * Get the failure code.
     */
    public function getFailureCode(): ?string
    {
        return $this->failureCode;
    }

    /**
     * Get the failure message.
     */
    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    /**
     * Get a metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if the payment requires manual review.
     */
    public function requiresManualReview(): bool
    {
        return (bool) ($this->metadata['requires_review'] ?? false);
    }

    /**
     * Check if a failed payment can be retried.
     */
    public function isRetryable(): bool
    {
        return (bool) ($this->metadata['retryable'] ?? false);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'payment_id' => $this->paymentId,
            'provider_transaction_id' => $this->providerTransactionId,
            'settled_amount' => $this->settledAmount?->getAmount(),
            'settled_currency' => $this->settledAmount?->getCurrency(),
            'failure_code' => $this->failureCode,
            'failure_message' => $this->failureMessage,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::RFC3339),
            'metadata' => $this->metadata ?: null,
        ], fn ($value) => $value !== null);
    }
}
