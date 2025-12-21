<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Contract for persisting payment rail transactions.
 */
interface RailTransactionPersistInterface
{
    /**
     * Save a transaction result.
     */
    public function save(RailTransactionResult $result): RailTransactionResult;

    /**
     * Update a transaction status.
     */
    public function updateStatus(
        string $transactionId,
        string $status,
        ?\DateTimeImmutable $settledAt = null,
    ): void;

    /**
     * Mark a transaction as settled.
     */
    public function markSettled(
        string $transactionId,
        \DateTimeImmutable $settledAt,
    ): void;

    /**
     * Mark a transaction as failed.
     *
     * @param array<string> $errors
     */
    public function markFailed(
        string $transactionId,
        array $errors,
    ): void;

    /**
     * Delete a transaction.
     */
    public function delete(string $transactionId): void;
}
