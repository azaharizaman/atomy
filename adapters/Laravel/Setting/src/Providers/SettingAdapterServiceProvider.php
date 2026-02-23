<?php

declare(strict_types=1);

namespace Nexus\Laravel\Setting\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Contracts\SettingsCacheInterface;
use Nexus\Setting\Contracts\SettingsAuthorizerInterface;
use Nexus\Laravel\Setting\Adapters\SettingRepositoryAdapter;
use Nexus\Laravel\Setting\Adapters\SettingsCacheAdapter;
use Nexus\Laravel\Setting\Adapters\SettingsAuthorizerAdapter;

/**
 * Laravel Service Provider for Setting package adapters.
 */
class SettingAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register cache adapter
        $this->app->singleton(SettingsCacheInterface::class, function ($app) {
            return new SettingsCacheAdapter(
                cache: $app['cache.store'],
                logger: $app['log']
            );
        });

        // Register repository adapter
        $this->app->singleton(SettingRepositoryInterface::class, function ($app) {
            return new SettingRepositoryAdapter(
                cache: $app->make(SettingsCacheInterface::class),
                logger: $app['log']
            );
        });

        // Register authorizer adapter
        $this->app->singleton(SettingsAuthorizerInterface::class, function ($app) {
            return new SettingsAuthorizerAdapter(
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
            __DIR__ . '/../../config/setting-adapter.php' => config_path('setting-adapter.php'),
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
            SettingRepositoryInterface::class,
            SettingsCacheInterface::class,
            SettingsAuthorizerInterface::class,
        ];
    }
}
