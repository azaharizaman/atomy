<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbTenantSettingsRepository;
use App\Repositories\DbUserSettingsRepository;
use App\Repositories\FileAppSettingsRepository;
use App\Services\LaravelSettingsCacheRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Contracts\SettingsCacheInterface;
use Nexus\Setting\Contracts\SettingsSchemaRegistryInterface;
use Nexus\Setting\Services\SettingsManager;
use Nexus\Setting\Services\SettingsSchemaRegistry;

/**
 * Settings service provider for binding Setting package contracts.
 *
 * This provider binds the framework-agnostic contracts to concrete
 * Laravel implementations for the three setting layers (user, tenant, application).
 */
class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind cache interface to Laravel implementation
        $this->app->singleton(SettingsCacheInterface::class, LaravelSettingsCacheRepository::class);

        // Bind schema registry
        $this->app->singleton(SettingsSchemaRegistryInterface::class, SettingsSchemaRegistry::class);

        // Bind repository interfaces with named bindings for each layer
        $this->app->bind('setting.repository.user', function ($app) {
            // Get user ID from authenticated user or request
            $userId = auth()->id() ?? request()->header('X-User-Id') ?? 'guest';

            return new DbUserSettingsRepository((string) $userId);
        });

        $this->app->bind('setting.repository.tenant', function ($app) {
            // Get tenant ID from authenticated user or request
            $tenantId = auth()->user()?->tenant_id
                ?? request()->header('X-Tenant-Id')
                ?? config('app.default_tenant_id')
                ?? 'default';

            return new DbTenantSettingsRepository((string) $tenantId);
        });

        $this->app->singleton('setting.repository.application', FileAppSettingsRepository::class);

        // Bind the main SettingsManager
        $this->app->singleton(SettingsManager::class, function ($app) {
            return new SettingsManager(
                userRepository: $app->make('setting.repository.user'),
                tenantRepository: $app->make('setting.repository.tenant'),
                applicationRepository: $app->make('setting.repository.application'),
                cache: $app->make(SettingsCacheInterface::class),
                protectedKeys: config('settings.protected_keys', [])
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/settings.php' => config_path('settings.php'),
        ], 'settings-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
