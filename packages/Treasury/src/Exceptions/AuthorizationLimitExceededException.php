<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

use Nexus\Common\ValueObjects\Money;

final class AuthorizationLimitExceededException extends TreasuryException
{
    public static function forAmount(Money $amount, Money $limit, string $userId): self
    {
        return new self(
            "Authorization limit exceeded for user {$userId}: " .
            "requested {$amount->format()}, limit is {$limit->format()}"
        );
    }

    public static function forTransaction(
        string $transactionType,
        Money $amount,
        Money $limit
    ): self {
        return new self(
            "Authorization limit exceeded for {$transactionType} transaction: " .
            "requested {$amount->format()}, limit is {$limit->format()}"
        );
    }

    public static function forRole(string $roleId, Money $amount, Money $limit): self
    {
        return new self(
            "Authorization limit exceeded for role {$roleId}: " .
            "requested {$amount->format()}, limit is {$limit->format()}"
        );
    }
}
