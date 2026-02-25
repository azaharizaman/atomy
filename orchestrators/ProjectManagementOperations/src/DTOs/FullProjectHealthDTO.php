<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\DTOs;

final readonly class FullProjectHealthDTO
{
    public function __construct(
        public LaborHealthDTO $laborHealth,
        public ExpenseHealthDTO $expenseHealth,
        public TimelineHealthDTO $timelineHealth,
        public float $overallScore
    ) {
    }
}
