<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;

final readonly class ExpenseHealthDTO
{
    public function __construct(
        public string $projectId,
        public Money $budgetedExpenseCost,
        public Money $actualExpenseCost,
        public float $healthPercentage
    ) {
    }
}
