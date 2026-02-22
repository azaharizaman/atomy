<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for budget threshold operations.
 */
final readonly class BudgetThresholdRequest
{
    public function __construct(
        public string $tenantId,
        public string $budgetId,
        public array $options = [],
    ) {}
}
