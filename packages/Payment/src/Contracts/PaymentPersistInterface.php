<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

/**
 * Persist interface for payment transactions (CQRS - Write).
 */
interface PaymentPersistInterface
{
    /**
     * Save a payment transaction (create or update).
     */
    public function save(PaymentTransactionInterface $payment): PaymentTransactionInterface;

    /**
     * Delete a payment transaction.
     */
    public function delete(string $id): void;

    /**
     * Store an idempotency key mapping.
     *
     * @param string $key The idempotency key
     * @param string $paymentId The associated payment ID
     * @param \DateTimeImmutable $expiresAt When the key expires
     */
    public function storeIdempotencyKey(
        string $key,
        string $paymentId,
        \DateTimeImmutable $expiresAt,
    ): void;

    /**
     * Check if an idempotency key exists and is not expired.
     */
    public function hasIdempotencyKey(string $key): bool;

    /**
     * Get the payment ID for an idempotency key.
     */
    public function getPaymentIdForIdempotencyKey(string $key): ?string;

    /**
     * Clean up expired idempotency keys.
     *
     * @return int Number of keys removed
     */
    public function cleanupExpiredIdempotencyKeys(): int;
}
