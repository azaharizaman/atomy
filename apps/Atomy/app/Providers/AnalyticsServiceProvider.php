<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Analytics\DbAnalyticsRepository;
use App\Services\Analytics\LaravelAnalyticsAuthorizer;
use App\Services\Analytics\LaravelAnalyticsContext;
use App\Services\Analytics\LaravelTransactionManager;
use Illuminate\Support\ServiceProvider;
use Nexus\Analytics\Contracts\AnalyticsManagerInterface;
use Nexus\Analytics\Contracts\AnalyticsRepositoryInterface;
use Nexus\Analytics\Contracts\AnalyticsAuthorizerInterface;
use Nexus\Analytics\Contracts\AnalyticsContextInterface;
use Nexus\Analytics\Core\Contracts\QueryExecutorInterface;
use Nexus\Analytics\Core\Contracts\TransactionManagerInterface;
use Nexus\Analytics\Core\Contracts\DataSourceAggregatorInterface;
use Nexus\Analytics\Core\Engine\QueryExecutor;
use Nexus\Analytics\Core\Engine\GuardEvaluator;
use Nexus\Analytics\Core\Engine\DataSourceAggregator;
use Nexus\Analytics\Services\AnalyticsManager;

/**
 * Analytics Service Provider
 * 
 * Binds all Analytics package contracts to Laravel implementations
 */
class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(AnalyticsRepositoryInterface::class, DbAnalyticsRepository::class);

        // Bind authorizer
        $this->app->singleton(AnalyticsAuthorizerInterface::class, LaravelAnalyticsAuthorizer::class);

        // Bind context (scoped to request)
        $this->app->scoped(AnalyticsContextInterface::class, LaravelAnalyticsContext::class);

        // Bind transaction manager
        $this->app->singleton(TransactionManagerInterface::class, LaravelTransactionManager::class);

        // Bind data source aggregator
        $this->app->singleton(DataSourceAggregatorInterface::class, DataSourceAggregator::class);

        // Bind query executor
        $this->app->singleton(QueryExecutorInterface::class, QueryExecutor::class);

        // Bind main manager
        $this->app->singleton(AnalyticsManagerInterface::class, AnalyticsManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
