<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbWorkflowRepository;
use App\Repositories\DbDefinitionRepository;
use App\Repositories\DbTaskRepository;
use App\Repositories\DbDelegationRepository;
use App\Repositories\DbTimerRepository;
use App\Repositories\DbHistoryRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Workflow\Contracts\WorkflowRepositoryInterface;
use Nexus\Workflow\Contracts\DefinitionRepositoryInterface;
use Nexus\Workflow\Contracts\TaskRepositoryInterface;
use Nexus\Workflow\Contracts\DelegationRepositoryInterface;
use Nexus\Workflow\Contracts\TimerRepositoryInterface;
use Nexus\Workflow\Contracts\HistoryRepositoryInterface;
use Nexus\Workflow\Contracts\ConditionEvaluatorInterface;
use Nexus\Workflow\Services\WorkflowManager;
use Nexus\Workflow\Services\TaskManager;
use Nexus\Workflow\Services\InboxService;
use Nexus\Workflow\Services\DelegationService;
use Nexus\Workflow\Services\SlaService;
use Nexus\Workflow\Services\EscalationService;
use Nexus\Workflow\Core\StateEngine;
use Nexus\Workflow\Core\ConditionEngine;

/**
 * Workflow Service Provider
 *
 * Binds workflow interfaces to concrete implementations
 */
class WorkflowServiceProvider extends ServiceProvider
{
    /**
     * Register workflow services.
     */
    public function register(): void
    {
        // Register repositories
        $this->app->singleton(WorkflowRepositoryInterface::class, DbWorkflowRepository::class);
        $this->app->singleton(DefinitionRepositoryInterface::class, DbDefinitionRepository::class);
        $this->app->singleton(TaskRepositoryInterface::class, DbTaskRepository::class);
        $this->app->singleton(DelegationRepositoryInterface::class, DbDelegationRepository::class);
        $this->app->singleton(TimerRepositoryInterface::class, DbTimerRepository::class);
        $this->app->singleton(HistoryRepositoryInterface::class, DbHistoryRepository::class);

        // Register core engine components
        $this->app->singleton(ConditionEvaluatorInterface::class, ConditionEngine::class);
        $this->app->singleton(StateEngine::class);

        // Register services
        $this->app->singleton(WorkflowManager::class);
        $this->app->singleton(TaskManager::class);
        $this->app->singleton(InboxService::class);
        $this->app->singleton(DelegationService::class);
        $this->app->singleton(SlaService::class);
        $this->app->singleton(EscalationService::class);
    }

    /**
     * Bootstrap workflow services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes if needed
        // $this->loadRoutesFrom(__DIR__ . '/../../routes/api_workflow.php');

        // Publish configuration if needed
        // $this->publishes([
        //     __DIR__ . '/../../config/workflow.php' => config_path('workflow.php'),
        // ], 'workflow-config');
    }
}
