<?php

declare(strict_types=1);

namespace Nexus\Compliance\Exceptions;

/**
 * Exception thrown when a duplicate SOD rule is created.
 */
class DuplicateRuleException extends \RuntimeException
{
    public function __construct(string $ruleName, string $transactionType)
    {
        parent::__construct(
            "SOD rule '{$ruleName}' for transaction type '{$transactionType}' already exists."
        );
    }
}
