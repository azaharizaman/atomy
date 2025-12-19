<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\ValueObjects\ExchangeRateSnapshot;
use Nexus\Payment\ValueObjects\ExecutionContext;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;

/**
 * Contract for payment transaction entities.
 *
 * Represents a single payment transaction in the system.
 * Payments can be inbound (receiving money) or outbound (sending money).
 *
 * Multi-currency payments are supported via:
 * - getOriginalAmount(): Amount in original currency
 * - getSettlementCurrency(): Currency for settlement
 * - getExchangeRateSnapshot(): Exchange rate at time of payment
 * - getSettlementAmount(): Amount converted to settlement currency
 */
interface PaymentTransactionInterface
{
    /**
     * Get the unique payment ID.
     */
    public function getId(): string;

    /**
     * Get the tenant ID this payment belongs to.
     */
    public function getTenantId(): string;

    /**
     * Get the payment reference.
     */
    public function getReference(): PaymentReference;

    /**
     * Get the payment direction (inbound/outbound).
     */
    public function getDirection(): PaymentDirection;

    /**
     * Get the current payment status.
     */
    public function getStatus(): PaymentStatus;

    /**
     * Get the payment amount.
     */
    public function getAmount(): Money;

    /**
     * Get the payment method type used.
     */
    public function getMethodType(): PaymentMethodType;

    /**
     * Get the payment method ID.
     */
    public function getMethodId(): ?string;

    /**
     * Get the payer ID (who is paying).
     */
    public function getPayerId(): ?string;

    /**
     * Get the payee ID (who is receiving).
     */
    public function getPayeeId(): ?string;

    /**
     * Get the description/memo for this payment.
     */
    public function getDescription(): ?string;

    /**
     * Get the external/provider transaction ID.
     */
    public function getProviderTransactionId(): ?string;

    /**
     * Get the idempotency key if set.
     */
    public function getIdempotencyKey(): ?IdempotencyKey;

    /**
     * Get the execution context.
     */
    public function getExecutionContext(): ?ExecutionContext;

    /**
     * Get the failure code if payment failed.
     */
    public function getFailureCode(): ?string;

    /**
     * Get the failure message if payment failed.
     */
    public function getFailureMessage(): ?string;

    /**
     * Get the date/time when the payment was initiated.
     */
    public function getInitiatedAt(): \DateTimeImmutable;

    /**
     * Get the date/time when the payment was completed.
     */
    public function getCompletedAt(): ?\DateTimeImmutable;

    /**
     * Get the date/time when the payment was cancelled.
     */
    public function getCancelledAt(): ?\DateTimeImmutable;

    /**
     * Get any additional metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get the number of execution attempts.
     */
    public function getAttemptCount(): int;

    /**
     * Check if the payment can transition to the given status.
     */
    public function canTransitionTo(PaymentStatus $status): bool;

    /**
     * Check if the payment is in a terminal state.
     */
    public function isTerminal(): bool;

    /**
     * Check if the payment is successful.
     */
    public function isSuccessful(): bool;

    /**
     * Check if the payment failed.
     */
    public function isFailed(): bool;

    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool;

    /**
     * Check if the payment can be cancelled.
     */
    public function canBeCancelled(): bool;

    /**
     * Check if the payment can be reversed.
     */
    public function canBeReversed(): bool;

    /**
     * Mark the payment as processing.
     */
    public function markAsProcessing(?string $providerTransactionId = null): void;

    /**
     * Mark the payment as completed.
     */
    public function markAsCompleted(
        Money $settledAmount,
        ?string $providerTransactionId = null,
    ): void;

    /**
     * Mark the payment as failed.
     */
    public function markAsFailed(string $failureCode, string $failureMessage): void;

    /**
     * Mark the payment as cancelled.
     */
    public function markAsCancelled(?string $reason = null): void;

    /**
     * Mark the payment as reversed.
     */
    public function markAsReversed(?string $reason = null, ?string $reversalTransactionId = null): void;

    // ========================================================================
    // Multi-Currency Support (PAY-060 to PAY-063)
    // ========================================================================

    /**
     * Get the original amount in the source currency.
     *
     * This is the amount as specified by the payment initiator,
     * before any currency conversion.
     */
    public function getOriginalAmount(): Money;

    /**
     * Get the settlement currency code.
     *
     * This is the currency that the payment will be settled in.
     * For same-currency payments, this equals the original currency.
     */
    public function getSettlementCurrency(): string;

    /**
     * Get the settlement amount in the settlement currency.
     *
     * For same-currency payments, this equals getAmount().
     * For cross-currency payments, this is the converted amount.
     *
     * Returns null if not yet calculated.
     */
    public function getSettlementAmount(): ?Money;

    /**
     * Get the exchange rate snapshot used for conversion.
     *
     * Returns null for same-currency payments or if not yet captured.
     */
    public function getExchangeRateSnapshot(): ?ExchangeRateSnapshot;

    /**
     * Check if this is a cross-currency payment.
     */
    public function isCrossCurrency(): bool;

    /**
     * Set the exchange rate snapshot for cross-currency conversion.
     *
     * This captures the rate at the time of payment creation.
     */
    public function setExchangeRateSnapshot(ExchangeRateSnapshot $snapshot): void;

    /**
     * Set the settlement amount after currency conversion.
     */
    public function setSettlementAmount(Money $amount): void;
}
