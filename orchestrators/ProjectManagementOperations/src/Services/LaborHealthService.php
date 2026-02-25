<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Services;

use Nexus\ProjectManagementOperations\Contracts\AttendanceQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\BudgetQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\DTOs\LaborHealthDTO;

final readonly class LaborHealthService
{
    public function __construct(
        private ProjectQueryInterface $projectQuery,
        private BudgetQueryInterface $budgetQuery,
        private AttendanceQueryInterface $attendanceQuery
    ) {
    }

    public function calculate(string $projectId): LaborHealthDTO
    {
        $project = $this->projectQuery->findById($projectId);
        if ($project === null) {
            throw new \InvalidArgumentException("Project with ID {$projectId} not found");
        }

        $budgetedLaborCost = $this->budgetQuery->getLaborBudget($projectId);
        $actualLaborCost = $this->budgetQuery->getActualLaborCost($projectId);
        
        $actualHours = $this->attendanceQuery->getTotalHoursByProject(
            $projectId,
            $project->startDate,
            $project->endDate
        );

        $healthPercentage = 0.0;
        if ($budgetedLaborCost->getAmountInMinorUnits() > 0) {
            $healthPercentage = ($actualLaborCost->getAmountInMinorUnits() / $budgetedLaborCost->getAmountInMinorUnits()) * 100;
        }

        return new LaborHealthDTO(
            projectId: $projectId,
            actualHours: $actualHours,
            budgetedLaborCost: $budgetedLaborCost,
            actualLaborCost: $actualLaborCost,
            healthPercentage: (float) $healthPercentage
        );
    }
}
