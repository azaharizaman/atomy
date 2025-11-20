<?php

declare(strict_types=1);

namespace Nexus\Budget\Exceptions;

use Nexus\Finance\ValueObjects\Money;

/**
 * Insufficient Budget For Transfer Exception
 * 
 * Thrown when attempting to transfer more than the available budget.
 */
final class InsufficientBudgetForTransferException extends BudgetException
{
    public function __construct(
        private readonly string $fromBudgetId,
        private readonly Money $requestedAmount,
        private readonly Money $availableAmount,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: sprintf(
            'Insufficient budget for transfer from %s: Requested %s, Available %s',
            $fromBudgetId,
            $requestedAmount->format(),
            $availableAmount->format()
        );
        parent::__construct($message, $code);
    }

    public function getFromBudgetId(): string
    {
        return $this->fromBudgetId;
    }

    public function getRequestedAmount(): Money
    {
        return $this->requestedAmount;
    }

    public function getAvailableAmount(): Money
    {
        return $this->availableAmount;
    }
}
