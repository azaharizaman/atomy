<?php

declare(strict_types=1);

namespace App\Services\ProjectManagementOperations;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\Contracts\LaborHealthServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\LaborHealthDTO;

final readonly class AtomyLaborHealthService implements LaborHealthServiceInterface
{
    public function calculate(string $projectId): LaborHealthDTO
    {
        $currency = 'USD';
        $budgeted = Money::zero($currency);
        $actual = Money::zero($currency);

        return new LaborHealthDTO(
            projectId: $projectId,
            actualHours: 0.0,
            budgetedLaborCost: $budgeted,
            actualLaborCost: $actual,
            healthPercentage: 100.0
        );
    }
}
