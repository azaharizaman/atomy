<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Identity\Contracts\CacheRepositoryInterface;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Laravel\Identity\Adapters\CacheRepositoryAdapter;
use Nexus\Laravel\Identity\Adapters\PermissionCheckerAdapter;

/**
 * Laravel Service Provider for Identity package adapters.
 */
class IdentityAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register cache repository adapter
        $this->app->singleton(CacheRepositoryInterface::class, function ($app) {
            return new CacheRepositoryAdapter(
                cache: $app['cache.store'],
                logger: $app['log']
            );
        });

        // Register permission checker adapter
        $this->app->singleton(PermissionCheckerInterface::class, function ($app) {
            return new PermissionCheckerAdapter(
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
            __DIR__ . '/../../config/identity-adapter.php' => config_path('identity-adapter.php'),
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
            CacheRepositoryInterface::class,
            PermissionCheckerInterface::class,
        ];
    }
}
