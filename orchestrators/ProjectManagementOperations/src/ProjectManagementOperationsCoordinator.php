<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations;

use Nexus\ProjectManagementOperations\Contracts\ExpenseHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\LaborHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\MilestoneBillingServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\TimelineDriftServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\FullProjectHealthDTO;

final readonly class ProjectManagementOperationsCoordinator
{
    public function __construct(
        private LaborHealthServiceInterface $laborService,
        private ExpenseHealthServiceInterface $expenseService,
        private TimelineDriftServiceInterface $timelineService,
        private MilestoneBillingServiceInterface $billingService
    ) {
    }

    public function getFullHealth(string $projectId): FullProjectHealthDTO
    {
        $laborHealth = $this->laborService->calculate($projectId);
        $expenseHealth = $this->expenseService->calculate($projectId);
        $timelineHealth = $this->timelineService->calculate($projectId);

        // Simple overall score calculation (average of health percentages)
        $overallScore = ($laborHealth->healthPercentage + $expenseHealth->healthPercentage + $timelineHealth->completionPercentage) / 3;

        return new FullProjectHealthDTO(
            laborHealth: $laborHealth,
            expenseHealth: $expenseHealth,
            timelineHealth: $timelineHealth,
            overallScore: (float) $overallScore
        );
    }
}
