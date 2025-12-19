<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentStatus;

/**
 * Query interface for payment transactions (CQRS - Read).
 */
interface PaymentQueryInterface
{
    /**
     * Find a payment by its ID.
     */
    public function findById(string $id): ?PaymentTransactionInterface;

    /**
     * Find a payment by its reference value.
     */
    public function findByReference(string $reference): ?PaymentTransactionInterface;

    /**
     * Find a payment by idempotency key.
     */
    public function findByIdempotencyKey(string $key): ?PaymentTransactionInterface;

    /**
     * Find a payment by provider transaction ID.
     */
    public function findByProviderTransactionId(string $providerTransactionId): ?PaymentTransactionInterface;

    /**
     * Find payments by payer ID.
     *
     * @return array<PaymentTransactionInterface>
     */
    public function findByPayerId(string $payerId): array;

    /**
     * Find payments by payee ID.
     *
     * @return array<PaymentTransactionInterface>
     */
    public function findByPayeeId(string $payeeId): array;

    /**
     * Find payments by status.
     *
     * @return array<PaymentTransactionInterface>
     */
    public function findByStatus(PaymentStatus $status): array;

    /**
     * Find payments by direction.
     *
     * @return array<PaymentTransactionInterface>
     */
    public function findByDirection(PaymentDirection $direction): array;

    /**
     * Find payments within a date range.
     *
     * @return array<PaymentTransactionInterface>
     */
    public function findByDateRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array;

    /**
     * Find pending payments older than the given threshold.
     *
     * @return array<PaymentTransactionInterface>
     */
    public function findStalePending(\DateTimeImmutable $olderThan): array;

    /**
     * Count payments by status.
     */
    public function countByStatus(PaymentStatus $status): int;

    /**
     * Get total amount by status and direction.
     */
    public function sumAmountByStatusAndDirection(
        PaymentStatus $status,
        PaymentDirection $direction,
        string $currency,
    ): float;
}
