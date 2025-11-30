<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

/**
 * Credit Limit Checker Interface
 *
 * Validates customer credit limits before order confirmation.
 * Supports both individual customer limits and customer group limits.
 */
interface CreditLimitCheckerInterface
{
    /**
     * Check if a customer can make a purchase within their credit limit
     *
     * @param string $tenantId
     * @param string $customerId
     * @param float $orderTotal
     * @param string $currencyCode
     * @return bool
     * @throws \Nexus\Receivable\Exceptions\CreditLimitExceededException
     */
    public function checkCreditLimit(
        string $tenantId,
        string $customerId,
        float $orderTotal,
        string $currencyCode
    ): bool;

    /**
     * Get customer's current outstanding balance
     *
     * @param string $tenantId
     * @param string $customerId
     * @return float
     */
    public function getOutstandingBalance(string $tenantId, string $customerId): float;

    /**
     * Get customer's credit limit
     *
     * @param string $customerId
     * @return float|null Null means unlimited credit
     */
    public function getCreditLimit(string $customerId): ?float;

    /**
     * Get customer's available credit
     *
     * @param string $tenantId
     * @param string $customerId
     * @return float|null Null means unlimited credit
     */
    public function getAvailableCredit(string $tenantId, string $customerId): ?float;

    /**
     * Check customer group credit limit
     *
     * @param string $tenantId
     * @param string $groupId
     * @param float $orderTotal
     * @return bool
     * @throws \Nexus\Receivable\Exceptions\CreditLimitExceededException
     */
    public function checkGroupCreditLimit(
        string $tenantId,
        string $groupId,
        float $orderTotal
    ): bool;
}
