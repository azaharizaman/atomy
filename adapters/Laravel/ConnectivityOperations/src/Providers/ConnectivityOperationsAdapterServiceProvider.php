<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\ConnectivityOperations\Contracts\ConnectivityTelemetryPortInterface;
use Nexus\ConnectivityOperations\Contracts\FeatureFlagPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderCallPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderCatalogPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderHealthStoreInterface;
use Nexus\ConnectivityOperations\Contracts\SecretRotationPortInterface;
use Nexus\Laravel\ConnectivityOperations\Adapters\CacheProviderHealthStoreAdapter;
use Nexus\Laravel\ConnectivityOperations\Adapters\ConnectivityTelemetryPortAdapter;
use Nexus\Laravel\ConnectivityOperations\Adapters\FeatureFlagPortAdapter;
use Nexus\Laravel\ConnectivityOperations\Adapters\ProviderCallPortAdapter;
use Nexus\Laravel\ConnectivityOperations\Adapters\ProviderCatalogPortAdapter;
use Nexus\Laravel\ConnectivityOperations\Adapters\SecretRotationPortAdapter;

final class ConnectivityOperationsAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProviderCallPortInterface::class, ProviderCallPortAdapter::class);
        $this->app->singleton(FeatureFlagPortInterface::class, FeatureFlagPortAdapter::class);
        $this->app->singleton(SecretRotationPortInterface::class, SecretRotationPortAdapter::class);
        $this->app->singleton(ConnectivityTelemetryPortInterface::class, ConnectivityTelemetryPortAdapter::class);
        $this->app->singleton(ProviderCatalogPortInterface::class, ProviderCatalogPortAdapter::class);
        $this->app->singleton(ProviderHealthStoreInterface::class, function ($app): CacheProviderHealthStoreAdapter {
            return new CacheProviderHealthStoreAdapter($app->make(CacheRepository::class));
        });
    }
}
