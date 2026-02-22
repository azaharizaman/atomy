<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for budget variance operations.
 */
final readonly class BudgetVarianceRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $options = [],
    ) {}
}
