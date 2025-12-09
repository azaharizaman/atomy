<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\ContractSpendContext;

/**
 * Contract for tracking cumulative spend against blanket POs/contracts.
 */
interface ContractSpendTrackerInterface
{
    /**
     * Get the current spend context for a blanket PO.
     *
     * @param string $blanketPoId Blanket PO identifier
     * @return ContractSpendContext|null Spend context or null if not found
     */
    public function getSpendContext(string $blanketPoId): ?ContractSpendContext;

    /**
     * Record spend against a blanket PO.
     *
     * @param string $blanketPoId Blanket PO identifier
     * @param int $amountCents Amount to record in cents
     * @param string $releaseOrderId Associated release order ID
     * @return int Updated cumulative spend in cents
     */
    public function recordSpend(string $blanketPoId, int $amountCents, string $releaseOrderId): int;

    /**
     * Reverse previously recorded spend (e.g., cancelled release order).
     *
     * @param string $blanketPoId Blanket PO identifier
     * @param int $amountCents Amount to reverse in cents
     * @param string $releaseOrderId Associated release order ID
     * @return int Updated cumulative spend in cents
     */
    public function reverseSpend(string $blanketPoId, int $amountCents, string $releaseOrderId): int;

    /**
     * Get all blanket POs approaching their spend limit.
     *
     * @param string $tenantId Tenant identifier
     * @param int $warningThresholdPercent Percentage threshold for warning (default 80)
     * @return array<ContractSpendContext> Blanket POs approaching limit
     */
    public function getApproachingLimit(string $tenantId, int $warningThresholdPercent = 80): array;

    /**
     * Get all blanket POs expiring within a date range.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $from Start date
     * @param \DateTimeImmutable $to End date
     * @return array<ContractSpendContext> Expiring blanket POs
     */
    public function getExpiringSoon(string $tenantId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
