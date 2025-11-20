<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbIntelligenceRepository;
use App\Services\LaravelIntelligenceContext;
use Illuminate\Support\ServiceProvider;
use Nexus\Intelligence\Contracts\AnomalyDetectionServiceInterface;
use Nexus\Intelligence\Contracts\IntelligenceContextInterface;
use Nexus\Intelligence\Contracts\ModelRepositoryInterface;
use Nexus\Intelligence\Core\Adapters\RuleBasedAnomalyEngine;
use Nexus\Intelligence\Services\IntelligenceManager;
use Psr\Log\LoggerInterface;

/**
 * Intelligence service provider
 */
class IntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(
            ModelRepositoryInterface::class,
            DbIntelligenceRepository::class
        );

        // Context binding (scoped to request)
        $this->app->scoped(
            IntelligenceContextInterface::class,
            LaravelIntelligenceContext::class
        );

        // Fallback engine
        $this->app->singleton(RuleBasedAnomalyEngine::class);

        // Main manager (primary service)
        $this->app->singleton(
            AnomalyDetectionServiceInterface::class,
            function ($app) {
                return new IntelligenceManager(
                    providers: [], // TODO: Add provider bindings in Phase 2
                    fallbackEngine: $app->make(RuleBasedAnomalyEngine::class),
                    repository: $app->make(ModelRepositoryInterface::class),
                    context: $app->make(IntelligenceContextInterface::class),
                    logger: $app->make(LoggerInterface::class)
                );
            }
        );

        // Alias for convenience
        $this->app->alias(AnomalyDetectionServiceInterface::class, 'intelligence');
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
