<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

use RuntimeException;

/**
 * Credit Limit Exceeded Exception
 *
 * Thrown when a transaction would cause a customer to exceed their credit limit.
 */
class CreditLimitExceededException extends RuntimeException
{
    public static function forCustomer(
        string $customerId,
        float $requestedAmount,
        float $creditLimit,
        float $currentBalance = 0.0
    ): self {
        $projectedBalance = $currentBalance + $requestedAmount;

        return new self(
            sprintf(
                'Credit limit exceeded for customer %s. ' .
                'Credit limit: %.2f, Current balance: %.2f, ' .
                'Requested amount: %.2f, Projected balance: %.2f',
                $customerId,
                $creditLimit,
                $currentBalance,
                $requestedAmount,
                $projectedBalance
            )
        );
    }

    public static function forCustomerGroup(
        string $groupId,
        float $requestedAmount,
        float $groupCreditLimit
    ): self {
        return new self(
            sprintf(
                'Customer group %s would exceed credit limit of %.2f with additional amount of %.2f',
                $groupId,
                $groupCreditLimit,
                $requestedAmount
            )
        );
    }
}
