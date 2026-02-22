<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for budget threshold operations.
 */
final readonly class BudgetThresholdResult
{
    public function __construct(
        public bool $success,
        public string $budgetId,
        public array $thresholds = [],
        public array $alerts = [],
        public ?string $errorMessage = null,
    ) {}
}
