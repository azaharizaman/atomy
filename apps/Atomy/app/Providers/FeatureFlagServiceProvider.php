<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbFlagRepository;
use App\Services\FeatureFlags\LaravelFlagCacheAdapter;
use Illuminate\Support\ServiceProvider;
use Nexus\FeatureFlags\Contracts\FlagCacheInterface;
use Nexus\FeatureFlags\Contracts\FlagEvaluatorInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Core\Decorators\CachedFlagRepository;
use Nexus\FeatureFlags\Core\Decorators\InMemoryMemoizedEvaluator;
use Nexus\FeatureFlags\Core\Decorators\MonitoredFlagManager;
use Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator;
use Nexus\FeatureFlags\Core\Engine\PercentageHasher;
use Nexus\FeatureFlags\Services\FeatureFlagManager;
use Psr\Log\LoggerInterface;

/**
 * Feature Flags Service Provider
 *
 * Binds Nexus\FeatureFlags contracts to Atomy implementations.
 */
final class FeatureFlagServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // ========================================
        // Cache Layer
        // ========================================

        $this->app->singleton(FlagCacheInterface::class, function ($app) {
            return new LaravelFlagCacheAdapter(
                cacheStore: config('feature-flags.cache_store', 'redis')
            );
        });

        // ========================================
        // Repository Layer
        // ========================================

        // Base repository (database)
        $this->app->singleton('feature-flags.repository.base', function ($app) {
            return new DbFlagRepository();
        });

        // Cached repository (decorator)
        $this->app->singleton(FlagRepositoryInterface::class, function ($app) {
            $baseRepository = $app->make('feature-flags.repository.base');
            $cache = $app->make(FlagCacheInterface::class);
            $logger = $app->make(LoggerInterface::class);
            $ttl = config('feature-flags.cache_ttl', 300);

            return new CachedFlagRepository($baseRepository, $cache, $logger, $ttl);
        });

        // ========================================
        // Evaluator Layer
        // ========================================

        // Base evaluator
        $this->app->singleton('feature-flags.evaluator.base', function ($app) {
            return new DefaultFlagEvaluator(new PercentageHasher());
        });

        // Memoized evaluator (decorator)
        $this->app->singleton(FlagEvaluatorInterface::class, function ($app) {
            $baseEvaluator = $app->make('feature-flags.evaluator.base');

            return new InMemoryMemoizedEvaluator($baseEvaluator);
        });

        // ========================================
        // Manager Layer
        // ========================================

        // Base manager
        $this->app->singleton('feature-flags.manager.base', function ($app) {
            return new FeatureFlagManager(
                repository: $app->make(FlagRepositoryInterface::class),
                evaluator: $app->make(FlagEvaluatorInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });

        // Monitored manager (decorator) - optional telemetry
        $this->app->singleton(FeatureFlagManagerInterface::class, function ($app) {
            $baseManager = $app->make('feature-flags.manager.base');

            // Optional telemetry (only if Nexus\Monitoring is installed)
            $telemetry = null;
            if ($app->bound(\Nexus\Monitoring\Contracts\TelemetryTrackerInterface::class)) {
                $telemetry = $app->make(\Nexus\Monitoring\Contracts\TelemetryTrackerInterface::class);
            }

            return new MonitoredFlagManager($baseManager, $telemetry);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/feature-flags.php' => config_path('feature-flags.php'),
        ], 'config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
