<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for budget check operations.
 */
final readonly class BudgetCheckRequest
{
    public function __construct(
        public string $tenantId,
        public string $budgetId,
        public float $amount,
        public array $options = [],
    ) {}
}
