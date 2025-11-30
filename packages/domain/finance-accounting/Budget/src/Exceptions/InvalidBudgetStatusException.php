<?php

declare(strict_types=1);

namespace Nexus\Budget\Exceptions;

use Nexus\Budget\Enums\BudgetStatus;

/**
 * Invalid Budget Status Exception
 * 
 * Thrown when an operation is attempted on a budget in an invalid status.
 */
final class InvalidBudgetStatusException extends BudgetException
{
    public function __construct(
        private readonly BudgetStatus $currentStatus,
        private readonly string $attemptedOperation,
        private readonly ?BudgetStatus $requiredStatus = null,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: sprintf(
            'Cannot %s budget in status "%s"%s',
            $attemptedOperation,
            $currentStatus->label(),
            $requiredStatus ? sprintf(' (requires status: %s)', $requiredStatus->label()) : ''
        );
        parent::__construct($message, $code);
    }

    public function getCurrentStatus(): BudgetStatus
    {
        return $this->currentStatus;
    }

    public function getAttemptedOperation(): string
    {
        return $this->attemptedOperation;
    }

    public function getRequiredStatus(): ?BudgetStatus
    {
        return $this->requiredStatus;
    }
}
