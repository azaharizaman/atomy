<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\{
use Nexus\Workflow\Services\{
    WorkflowRepositoryInterface,
    WorkflowDefinitionInterface,
    DefinitionRepositoryInterface,
    TaskRepositoryInterface,
    DelegationRepositoryInterface,
    TimerRepositoryInterface,
    HistoryRepositoryInterface,
    ConditionEvaluatorInterface
};
use Nexus\Workflow\Contracts\{
    WorkflowManager,
    TaskManager,
    InboxService,
    DelegationService,
    SlaService,
    EscalationService
};
use Illuminate\Support\ServiceProvider;
use Nexus\Workflow\Core\{StateEngine, ConditionEngine};
    DbWorkflowRepository,
    DbDefinitionRepository,
    DbTaskRepository,
    DbDelegationRepository,
    DbTimerRepository,
    DbHistoryRepository
};

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
