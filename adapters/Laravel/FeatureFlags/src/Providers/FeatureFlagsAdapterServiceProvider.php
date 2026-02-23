<?php

declare(strict_types=1);

namespace Nexus\Laravel\FeatureFlags\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\FeatureFlags\Contracts\FlagCacheInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\Laravel\FeatureFlags\Adapters\FlagCacheAdapter;
use Nexus\Laravel\FeatureFlags\Adapters\FlagRepositoryAdapter;

/**
 * Laravel Service Provider for FeatureFlags package adapters.
 */
class FeatureFlagsAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register cache adapter
        $this->app->singleton(FlagCacheInterface::class, function ($app) {
            return new FlagCacheAdapter(
                cache: $app['cache.store'],
                logger: $app['log']
            );
        });

        // Register repository adapter
        $this->app->singleton(FlagRepositoryInterface::class, function ($app) {
            return new FlagRepositoryAdapter(
                cache: $app->make(FlagCacheInterface::class),
                logger: $app['log']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/featureflags-adapter.php' => config_path('featureflags-adapter.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            FlagCacheInterface::class,
            FlagRepositoryInterface::class,
        ];
    }
}
