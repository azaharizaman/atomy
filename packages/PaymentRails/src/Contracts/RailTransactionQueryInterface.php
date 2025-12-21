<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Contract for querying payment rail transactions.
 */
interface RailTransactionQueryInterface
{
    /**
     * Find a transaction by its unique identifier.
     */
    public function findById(string $transactionId): ?RailTransactionResult;

    /**
     * Find a transaction by its reference number.
     */
    public function findByReference(string $referenceNumber): ?RailTransactionResult;

    /**
     * Find transactions by rail type.
     *
     * @return array<RailTransactionResult>
     */
    public function findByRailType(RailType $railType, int $limit = 100, int $offset = 0): array;

    /**
     * Find transactions by status.
     *
     * @return array<RailTransactionResult>
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array;

    /**
     * Find transactions within a date range.
     *
     * @return array<RailTransactionResult>
     */
    public function findByDateRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?RailType $railType = null,
    ): array;

    /**
     * Find pending transactions.
     *
     * @return array<RailTransactionResult>
     */
    public function findPending(?RailType $railType = null): array;

    /**
     * Find failed transactions.
     *
     * @return array<RailTransactionResult>
     */
    public function findFailed(?RailType $railType = null, int $limit = 100): array;
}
