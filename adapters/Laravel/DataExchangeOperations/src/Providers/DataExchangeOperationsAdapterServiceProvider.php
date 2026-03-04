<?php

declare(strict_types=1);

namespace Nexus\Laravel\DataExchangeOperations\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\DataExchangeOperations\Contracts\DataExchangeTaskStoreInterface;
use Nexus\DataExchangeOperations\Contracts\DataExportPortInterface;
use Nexus\DataExchangeOperations\Contracts\DataImportPortInterface;
use Nexus\DataExchangeOperations\Contracts\NotificationPortInterface;
use Nexus\DataExchangeOperations\Contracts\StoragePortInterface;
use Nexus\Import\Contracts\TransactionManagerInterface;
use Nexus\Import\Services\ImportManager;
use Nexus\Laravel\DataExchangeOperations\Adapters\CacheDataExchangeTaskStoreAdapter;
use Nexus\Laravel\DataExchangeOperations\Adapters\ExportPortAdapter;
use Nexus\Laravel\DataExchangeOperations\Adapters\ImportPortAdapter;
use Nexus\Laravel\DataExchangeOperations\Adapters\NotificationPortAdapter;
use Nexus\Laravel\DataExchangeOperations\Adapters\StoragePortAdapter;

final class DataExchangeOperationsAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataImportPortInterface::class, function ($app): ImportPortAdapter {
            return new ImportPortAdapter(
                importManager: $app->make(ImportManager::class),
                transactionManager: $app->bound(TransactionManagerInterface::class)
                    ? $app->make(TransactionManagerInterface::class)
                    : null,
                logger: $app['log']
            );
        });

        $this->app->singleton(DataExportPortInterface::class, ExportPortAdapter::class);
        $this->app->singleton(StoragePortInterface::class, StoragePortAdapter::class);
        $this->app->singleton(NotificationPortInterface::class, NotificationPortAdapter::class);
        $this->app->singleton(DataExchangeTaskStoreInterface::class, function ($app): CacheDataExchangeTaskStoreAdapter {
            return new CacheDataExchangeTaskStoreAdapter($app->make(CacheRepository::class));
        });
    }
}
