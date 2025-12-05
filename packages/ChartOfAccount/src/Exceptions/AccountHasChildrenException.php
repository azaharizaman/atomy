<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Exceptions;

/**
 * Exception thrown when attempting to delete an account that has child accounts.
 */
class AccountHasChildrenException extends ChartOfAccountException
{
    /**
     * Create exception for account with children.
     *
     * @param string $id Account ID
     * @param int $childCount Number of child accounts
     */
    public static function create(string $id, int $childCount = 0): self
    {
        $message = $childCount > 0
            ? sprintf('Cannot delete account %s: has %d child account(s)', $id, $childCount)
            : sprintf('Cannot delete account %s: has child accounts', $id);

        return new self($message);
    }
}
