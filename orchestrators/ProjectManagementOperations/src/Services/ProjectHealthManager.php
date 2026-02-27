<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Services;

use Nexus\ProjectManagementOperations\Contracts\AttendanceQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\BudgetQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\SchedulerQueryInterface;

final readonly class ProjectHealthManager
{
    public function __construct(
        private ProjectQueryInterface $projectQuery,
        private BudgetQueryInterface $budgetQuery,
        private AttendanceQueryInterface $attendanceQuery,
        private SchedulerQueryInterface $schedulerQuery
    ) {
    }
}
