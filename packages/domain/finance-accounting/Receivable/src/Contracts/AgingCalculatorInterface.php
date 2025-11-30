<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeInterface;

/**
 * Aging Calculator Interface
 *
 * Calculates Accounts Receivable aging reports by bucket.
 */
interface AgingCalculatorInterface
{
    /**
     * Calculate aging report for a customer
     *
     * @param string $tenantId
     * @param string $customerId
     * @param DateTimeInterface $asOfDate
     * @return array<string, mixed> ['current' => float, '1_30' => float, '31_60' => float, ...]
     */
    public function calculateCustomerAging(
        string $tenantId,
        string $customerId,
        DateTimeInterface $asOfDate
    ): array;

    /**
     * Calculate aging report for all customers
     *
     * @param string $tenantId
     * @param DateTimeInterface $asOfDate
     * @return array<string, mixed>[] Array of customer aging data
     */
    public function calculateAgingReport(
        string $tenantId,
        DateTimeInterface $asOfDate
    ): array;

    /**
     * Get aging buckets configuration
     *
     * @return \Nexus\Receivable\ValueObjects\AgingBucket[]
     */
    public function getAgingBuckets(): array;
}
