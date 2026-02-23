<?php

declare(strict_types=1);

namespace Nexus\Laravel\Monitoring\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Nexus\Laravel\Monitoring\Adapters\TelemetryTrackerAdapter;
use Nexus\Laravel\Monitoring\Adapters\MetricStorageAdapter;

/**
 * Laravel Service Provider for Monitoring package adapters.
 */
class MonitoringAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register telemetry tracker adapter
        $this->app->singleton(TelemetryTrackerInterface::class, function ($app) {
            return new TelemetryTrackerAdapter(
                logger: $app['log']
            );
        });

        // Register metric storage adapter
        $this->app->singleton(MetricStorageInterface::class, function ($app) {
            return new MetricStorageAdapter(
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
            __DIR__ . '/../../config/monitoring-adapter.php' => config_path('monitoring-adapter.php'),
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
            TelemetryTrackerInterface::class,
            MetricStorageInterface::class,
        ];
    }
}
