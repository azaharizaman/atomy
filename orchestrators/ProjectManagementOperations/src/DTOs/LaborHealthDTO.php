<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;

final readonly class LaborHealthDTO
{
    public function __construct(
        public string $projectId,
        public float $actualHours,
        public Money $budgetedLaborCost,
        public Money $actualLaborCost,
        public float $healthPercentage
    ) {
    }
}
