<?php

declare(strict_types=1);

namespace Nexus\Budget\Exceptions;

/**
 * Budget Not Found Exception
 * 
 * Thrown when a requested budget does not exist.
 */
final class BudgetNotFoundException extends BudgetException
{
    public function __construct(
        private readonly string $budgetId,
        string $message = '',
        int $code = 404
    ) {
        $message = $message ?: "Budget not found: {$budgetId}";
        parent::__construct($message, $code);
    }

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }
}
