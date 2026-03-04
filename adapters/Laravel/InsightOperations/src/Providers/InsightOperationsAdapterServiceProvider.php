<?php

declare(strict_types=1);

namespace Nexus\Laravel\InsightOperations\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\InsightOperations\Contracts\DashboardSnapshotPortInterface;
use Nexus\InsightOperations\Contracts\ForecastPortInterface;
use Nexus\InsightOperations\Contracts\InsightNotificationPortInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\InsightOperations\Contracts\ReportDataQueryPortInterface;
use Nexus\InsightOperations\Contracts\ReportExportPortInterface;
use Nexus\Laravel\InsightOperations\Adapters\DashboardSnapshotPortAdapter;
use Nexus\Laravel\InsightOperations\Adapters\ForecastPortAdapter;
use Nexus\Laravel\InsightOperations\Adapters\InsightNotificationPortAdapter;
use Nexus\Laravel\InsightOperations\Adapters\InsightStoragePortAdapter;
use Nexus\Laravel\InsightOperations\Adapters\ReportDataQueryPortAdapter;
use Nexus\Laravel\InsightOperations\Adapters\ReportExportPortAdapter;

final class InsightOperationsAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReportDataQueryPortInterface::class, ReportDataQueryPortAdapter::class);
        $this->app->singleton(ForecastPortInterface::class, ForecastPortAdapter::class);
        $this->app->singleton(ReportExportPortInterface::class, ReportExportPortAdapter::class);
        $this->app->singleton(InsightStoragePortInterface::class, InsightStoragePortAdapter::class);
        $this->app->singleton(InsightNotificationPortInterface::class, InsightNotificationPortAdapter::class);
        $this->app->singleton(DashboardSnapshotPortInterface::class, DashboardSnapshotPortAdapter::class);
    }
}
