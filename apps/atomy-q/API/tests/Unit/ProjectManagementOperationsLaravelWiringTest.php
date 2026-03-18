<?php

declare(strict_types=1);

namespace Tests\Unit;

use Nexus\ProjectManagementOperations\Contracts\ExpenseHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\LaborHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\MilestoneBillingServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\TimelineDriftServiceInterface;
use Nexus\ProjectManagementOperations\ProjectManagementOperationsCoordinator;
use Tests\TestCase;

class ProjectManagementOperationsLaravelWiringTest extends TestCase
{
    public function test_coordinator_and_health_services_are_bound(): void
    {
        $this->assertInstanceOf(
            ProjectManagementOperationsCoordinator::class,
            $this->app->make(ProjectManagementOperationsCoordinator::class)
        );

        $this->assertInstanceOf(
            LaborHealthServiceInterface::class,
            $this->app->make(LaborHealthServiceInterface::class)
        );

        $this->assertInstanceOf(
            ExpenseHealthServiceInterface::class,
            $this->app->make(ExpenseHealthServiceInterface::class)
        );

        $this->assertInstanceOf(
            TimelineDriftServiceInterface::class,
            $this->app->make(TimelineDriftServiceInterface::class)
        );

        $this->assertInstanceOf(
            MilestoneBillingServiceInterface::class,
            $this->app->make(MilestoneBillingServiceInterface::class)
        );
    }

    public function test_project_task_ids_query_contract_is_bound(): void
    {
        $this->assertInstanceOf(
            ProjectTaskIdsQueryInterface::class,
            $this->app->make(ProjectTaskIdsQueryInterface::class)
        );
    }
}
