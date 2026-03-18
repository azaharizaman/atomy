<?php

declare(strict_types=1);

namespace App\Services\ProjectManagementOperations;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\Contracts\ExpenseHealthServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\ExpenseHealthDTO;

final readonly class AtomyExpenseHealthService implements ExpenseHealthServiceInterface
{
    public function calculate(string $projectId): ExpenseHealthDTO
    {
        $currency = 'USD';

        return new ExpenseHealthDTO(
            projectId: $projectId,
            budgetedExpenseCost: Money::zero($currency),
            actualExpenseCost: Money::zero($currency),
            healthPercentage: 100.0
        );
    }
}
