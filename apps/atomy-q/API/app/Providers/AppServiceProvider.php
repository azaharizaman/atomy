<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\JwtServiceInterface;
use App\Services\JwtService;
use App\Services\Project\AtomyIncompleteTaskCount;
use App\Services\Project\AtomyProjectPersist;
use App\Services\Project\AtomyProjectQuery;
use App\Services\Task\AtomyTaskPersist;
use App\Services\Task\AtomyTaskQuery;
use Illuminate\Support\ServiceProvider;
use Nexus\Project\Contracts\IncompleteTaskCountInterface;
use Nexus\Project\Contracts\ProjectManagerInterface;
use Nexus\Project\Contracts\ProjectPersistInterface;
use Nexus\Project\Contracts\ProjectQueryInterface;
use Nexus\Project\Services\ProjectManager;
use Nexus\Task\Contracts\DependencyGraphInterface;
use Nexus\Task\Services\DependencyGraphService;
use Nexus\Task\Services\TaskManager;
use Nexus\ProjectManagementOperations\Contracts\ExpenseHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\LaborHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\MilestoneBillingServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\TimelineDriftServiceInterface;
use Nexus\ProjectManagementOperations\ProjectManagementOperationsCoordinator;
use Nexus\Task\Contracts\TaskManagerInterface;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\Contracts\TaskQueryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JwtServiceInterface::class, function (): JwtServiceInterface {
            $secret = (string) config('jwt.secret');

            if ($secret === '') {
                throw new \InvalidArgumentException('JWT_SECRET is not set or empty in environment configuration.');
            }

            return new JwtService(
                $secret,
                (int) config('jwt.ttl'),
                (int) config('jwt.refresh_ttl'),
                (string) config('jwt.algo'),
                (string) config('jwt.issuer'),
            );
        });

        // Nexus Project: Laravel implementations + package Manager.
        $this->app->bind(ProjectPersistInterface::class, AtomyProjectPersist::class);
        $this->app->bind(ProjectQueryInterface::class, AtomyProjectQuery::class);
        $this->app->bind(IncompleteTaskCountInterface::class, AtomyIncompleteTaskCount::class);
        $this->app->bind(ProjectManagerInterface::class, ProjectManager::class);

        // Nexus Task: Laravel implementations + package Manager and DependencyGraph.
        $this->app->bind(TaskPersistInterface::class, AtomyTaskPersist::class);
        $this->app->bind(TaskQueryInterface::class, AtomyTaskQuery::class);
        $this->app->bind(DependencyGraphInterface::class, DependencyGraphService::class);
        $this->app->bind(TaskManagerInterface::class, TaskManager::class);

        // ProjectManagementOperations orchestrator and health services.
        $this->app->bind(LaborHealthServiceInterface::class, \App\Services\ProjectManagementOperations\AtomyLaborHealthService::class);
        $this->app->bind(ExpenseHealthServiceInterface::class, \App\Services\ProjectManagementOperations\AtomyExpenseHealthService::class);
        $this->app->bind(TimelineDriftServiceInterface::class, \App\Services\ProjectManagementOperations\AtomyTimelineDriftService::class);
        $this->app->bind(MilestoneBillingServiceInterface::class, \App\Services\ProjectManagementOperations\AtomyMilestoneBillingService::class);
        $this->app->singleton(ProjectManagementOperationsCoordinator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
