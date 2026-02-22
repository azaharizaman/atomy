<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for budget check operations.
 */
final readonly class BudgetCheckResult
{
    public function __construct(
        public bool $success,
        public string $budgetId,
        public string $availableAmount,
        public bool $isAvailable,
        public ?string $errorMessage = null,
    ) {}
}
