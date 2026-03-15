<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\ProjectManagementOperations\Contracts\AttendanceQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\BudgetPersistInterface;
use Nexus\ProjectManagementOperations\Contracts\BudgetQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\MessagingServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ReceivablePersistInterface;
use Nexus\ProjectManagementOperations\Contracts\SchedulerQueryInterface;
use Nexus\Laravel\ProjectManagementOperations\Adapters\AttendanceQueryAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\BudgetPersistAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\BudgetQueryAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\MessagingServiceAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\ProjectQueryAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\ReceivablePersistAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\SchedulerQueryAdapter;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetPersistInterface;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetQueryInterface;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectMessagingSenderInterface;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;

/**
 * Binds ProjectManagementOperations orchestrator contracts to adapters that use L1 packages.
 * The app must register: ProjectTaskIdsQueryInterface, ProjectBudgetQueryInterface,
 * ProjectBudgetPersistInterface, ProjectMessagingSenderInterface, and L1 query/persist implementations.
 */
class ProjectManagementOperationsAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProjectQueryInterface::class, function ($app) {
            return new ProjectQueryAdapter(
                $app->make(\Nexus\Project\Contracts\ProjectQueryInterface::class),
                $app->make(\Nexus\Milestone\Contracts\MilestoneQueryInterface::class)
            );
        });

        $this->app->bind(AttendanceQueryInterface::class, function ($app) {
            return new AttendanceQueryAdapter(
                $app->make(ProjectTaskIdsQueryInterface::class),
                $app->make(\Nexus\TimeTracking\Contracts\TimesheetQueryInterface::class)
            );
        });

        $this->app->bind(BudgetQueryInterface::class, function ($app) {
            return new BudgetQueryAdapter(
                $app->make(ProjectBudgetQueryInterface::class)
            );
        });

        $this->app->bind(BudgetPersistInterface::class, function ($app) {
            return new BudgetPersistAdapter(
                $app->make(ProjectBudgetPersistInterface::class)
            );
        });

        $this->app->bind(SchedulerQueryInterface::class, function ($app) {
            return new SchedulerQueryAdapter(
                $app->make(\Nexus\Project\Contracts\ProjectQueryInterface::class)
            );
        });

        $this->app->bind(ReceivablePersistInterface::class, function ($app) {
            return new ReceivablePersistAdapter(
                $app->make(\Nexus\Receivable\Contracts\ReceivableManagerInterface::class)
            );
        });

        $this->app->bind(MessagingServiceInterface::class, function ($app) {
            return new MessagingServiceAdapter(
                $app->make(ProjectMessagingSenderInterface::class)
            );
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ProjectQueryInterface::class,
            AttendanceQueryInterface::class,
            BudgetQueryInterface::class,
            BudgetPersistInterface::class,
            SchedulerQueryInterface::class,
            ReceivablePersistInterface::class,
            MessagingServiceInterface::class,
        ];
    }
}
