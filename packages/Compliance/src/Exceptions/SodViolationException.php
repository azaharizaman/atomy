<?php

declare(strict_types=1);

namespace Nexus\Compliance\Exceptions;

/**
 * Exception thrown when a SOD (Segregation of Duties) violation is detected.
 */
class SodViolationException extends \RuntimeException
{
    public function __construct(
        string $transactionType,
        string $creatorId,
        string $approverId,
        string $ruleName
    ) {
        parent::__construct(
            "SOD violation detected for transaction type '{$transactionType}': " .
            "User '{$approverId}' cannot approve transaction created by '{$creatorId}' " .
            "according to rule '{$ruleName}'."
        );
    }
}
