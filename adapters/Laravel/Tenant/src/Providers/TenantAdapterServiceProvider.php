<?php

declare(strict_types=1);

namespace Nexus\Laravel\Tenant\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\Tenant\Contracts\CacheRepositoryInterface;
use Nexus\Tenant\Contracts\EventDispatcherInterface;
use Nexus\Tenant\Contracts\ImpersonationStorageInterface;
use Nexus\Laravel\Tenant\Adapters\TenantContextAdapter;
use Nexus\Laravel\Tenant\Adapters\TenantPersistenceAdapter;
use Nexus\Laravel\Tenant\Adapters\TenantQueryAdapter;
use Nexus\Laravel\Tenant\Adapters\CacheRepositoryAdapter;
use Nexus\Laravel\Tenant\Adapters\EventDispatcherAdapter;
use Nexus\Laravel\Tenant\Adapters\ImpersonationStorageAdapter;

/**
 * Laravel Service Provider for Tenant package adapters.
 *
 * This provider binds the Tenant package interfaces to their concrete
 * adapter implementations that integrate with the Laravel framework.
 */
class TenantAdapterServiceProvider extends ServiceProvider
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

        // Register event dispatcher adapter
        $this->app->singleton(EventDispatcherInterface::class, function ($app) {
            return new EventDispatcherAdapter(
                dispatcher: $app['events']
            );
        });

        // Register tenant context adapter (scoped to request)
        $this->app->singleton(TenantContextInterface::class, function ($app) {
            return new TenantContextAdapter(
                cacheRepository: $app->make(CacheRepositoryInterface::class),
                logger: $app['log']
            );
        });

        // Register tenant persistence adapter
        $this->app->singleton(TenantPersistenceInterface::class, function ($app) {
            return new TenantPersistenceAdapter(
                logger: $app['log']
            );
        });

        // Register tenant query adapter
        $this->app->singleton(TenantQueryInterface::class, function ($app) {
            return new TenantQueryAdapter(
                logger: $app['log']
            );
        });

        // Register impersonation storage adapter
        $this->app->singleton(ImpersonationStorageInterface::class, function ($app) {
            return new ImpersonationStorageAdapter(
                cache: $app['cache.store'],
                logger: $app['log']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration if needed
        $this->publishes([
            __DIR__ . '/../../config/tenant-adapter.php' => config_path('tenant-adapter.php'),
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
            TenantContextInterface::class,
            TenantPersistenceInterface::class,
            TenantQueryInterface::class,
            CacheRepositoryInterface::class,
            EventDispatcherInterface::class,
            ImpersonationStorageInterface::class,
        ];
    }
}
