<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for budget variance operations.
 */
final readonly class BudgetVarianceResult
{
    public function __construct(
        public bool $success,
        public string $periodId,
        public array $variances = [],
        public ?string $errorMessage = null,
    ) {}
}
