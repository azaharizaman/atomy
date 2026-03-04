<?php

declare(strict_types=1);

namespace Nexus\Laravel\IntelligenceOperations\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\IntelligenceOperations\Contracts\DataDriftPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelRegistryPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelTelemetryPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelTrainingPortInterface;
use Nexus\Laravel\IntelligenceOperations\Adapters\DataDriftPortAdapter;
use Nexus\Laravel\IntelligenceOperations\Adapters\ModelRegistryPortAdapter;
use Nexus\Laravel\IntelligenceOperations\Adapters\ModelTelemetryPortAdapter;
use Nexus\Laravel\IntelligenceOperations\Adapters\ModelTrainingPortAdapter;
use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;

final class IntelligenceOperationsAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModelRegistryPortInterface::class, ModelRegistryPortAdapter::class);
        $this->app->singleton(DataDriftPortInterface::class, DataDriftPortAdapter::class);
        $this->app->singleton(ModelTrainingPortInterface::class, ModelTrainingPortAdapter::class);

        $this->app->singleton(ModelTelemetryPortInterface::class, function ($app): ModelTelemetryPortAdapter {
            return new ModelTelemetryPortAdapter(
                telemetry: $app->make(TelemetryTrackerInterface::class),
                cache: $app->make(CacheRepository::class),
            );
        });
    }
}
