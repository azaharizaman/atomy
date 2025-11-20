<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

/**
 * Credit limit enforcement service contract.
 * V1: Stub implementation (NoOpCreditLimitChecker returns true).
 * Phase 2: Integrate with Nexus\Receivable for real-time credit checks.
 */
interface CreditLimitCheckerInterface
{
    /**
     * Check if customer has sufficient credit for the order.
     *
     * @param string $tenantId
     * @param string $customerId
     * @param float $orderTotal Total order amount
     * @param string $currencyCode
     * @return bool TRUE if order is within credit limit, FALSE otherwise
     * @throws \Nexus\Sales\Exceptions\CreditLimitExceededException
     */
    public function checkCreditLimit(
        string $tenantId,
        string $customerId,
        float $orderTotal,
        string $currencyCode
    ): bool;
}
