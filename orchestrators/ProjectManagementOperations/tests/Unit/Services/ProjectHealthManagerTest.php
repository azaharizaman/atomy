<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Tests\Unit\Services;

use Nexus\ProjectManagementOperations\Contracts\AttendanceQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\BudgetQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\SchedulerQueryInterface;
use Nexus\ProjectManagementOperations\Services\ProjectHealthManager;
use PHPUnit\Framework\TestCase;

final class ProjectHealthManagerTest extends TestCase
{
    public function test_it_can_be_instantiated_with_required_contracts(): void
    {
        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $budgetQuery = $this->createMock(BudgetQueryInterface::class);
        $attendanceQuery = $this->createMock(AttendanceQueryInterface::class);
        $schedulerQuery = $this->createMock(SchedulerQueryInterface::class);

        $manager = new ProjectHealthManager(
            $projectQuery,
            $budgetQuery,
            $attendanceQuery,
            $schedulerQuery
        );

        $this->assertInstanceOf(ProjectHealthManager::class, $manager);
    }
}
