<?php
declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor;

use Illuminate\Support\ServiceProvider;
use Nexus\Vendor\Contracts\VendorQueryInterface;
use Nexus\Vendor\Contracts\VendorPersistInterface;
use Nexus\Adapter\Laravel\Vendor\Repositories\EloquentVendorRepository;

class VendorServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(VendorQueryInterface::class, EloquentVendorRepository::class);
        $this->app->bind(VendorPersistInterface::class, EloquentVendorRepository::class);
    }

    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
