<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor;

use Illuminate\Support\ServiceProvider;
use Nexus\Adapter\Laravel\Vendor\Repositories\EloquentVendorRepository;
use Nexus\Vendor\Contracts\VendorPersistInterface;
use Nexus\Vendor\Contracts\VendorQueryInterface;
use Nexus\Vendor\Contracts\VendorRepositoryInterface;
use Nexus\Vendor\Contracts\VendorStatusTransitionPolicyInterface;
use Nexus\Vendor\Services\VendorStatusTransitionPolicy;

final class VendorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EloquentVendorRepository::class);
        $this->app->bind(VendorRepositoryInterface::class, EloquentVendorRepository::class);
        $this->app->bind(VendorQueryInterface::class, EloquentVendorRepository::class);
        $this->app->bind(VendorPersistInterface::class, EloquentVendorRepository::class);
        $this->app->singleton(VendorStatusTransitionPolicyInterface::class, VendorStatusTransitionPolicy::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
