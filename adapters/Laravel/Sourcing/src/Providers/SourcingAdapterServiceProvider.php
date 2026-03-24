<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sourcing\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Laravel\Sourcing\Repositories\EloquentQuotationRepository;
use Nexus\Sourcing\Contracts\QuotationQueryInterface;

final class SourcingAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QuotationQueryInterface::class, EloquentQuotationRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
