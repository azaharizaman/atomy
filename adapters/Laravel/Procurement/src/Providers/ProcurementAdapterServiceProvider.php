<?php

declare(strict_types=1);

namespace Nexus\Adapters\Laravel\Procurement\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Adapters\Laravel\Procurement\Adapters\LaravelDatabaseTransactionAdapter;
use Nexus\Procurement\Contracts\DatabaseTransactionInterface;

final class ProcurementAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            DatabaseTransactionInterface::class,
            LaravelDatabaseTransactionAdapter::class
        );
    }
}
