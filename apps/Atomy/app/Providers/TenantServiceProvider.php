<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Tenant\Contracts\CacheRepositoryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Tenant\Services\TenantContextManager;
use Nexus\Tenant\Services\TenantEventDispatcher;
use Nexus\Tenant\Services\TenantImpersonationService;
use Nexus\Tenant\Services\TenantLifecycleService;
use Nexus\Tenant\Services\TenantResolverService;
use App\Repositories\DbTenantRepository;
use App\Services\LaravelCacheRepository;

/**
 * Tenant Service Provider
 *
 * Registers and binds all Tenant package services in Laravel's IoC container.
 */
class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to concrete implementations
        $this->app->singleton(TenantRepositoryInterface::class, DbTenantRepository::class);
        $this->app->singleton(CacheRepositoryInterface::class, LaravelCacheRepository::class);

        // Bind context manager
        $this->app->singleton(TenantContextInterface::class, TenantContextManager::class);
        $this->app->singleton(TenantContextManager::class);

        // Bind event dispatcher
        $this->app->singleton(TenantEventDispatcher::class);

        // Bind services
        $this->app->singleton(TenantLifecycleService::class);
        $this->app->singleton(TenantImpersonationService::class);
        $this->app->singleton(TenantResolverService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        if (file_exists(__DIR__ . '/../../routes/api_tenant.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api_tenant.php');
        }

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/tenant.php' => config_path('tenant.php'),
        ], 'tenant-config');
    }
}
