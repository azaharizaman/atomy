<?php
declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor;

use Illuminate\Support\ServiceProvider;
use Nexus\Vendor\Contracts\VendorRepositoryInterface;
use Nexus\Adapter\Laravel\Vendor\Repositories\EloquentVendorRepository;

class VendorServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(VendorRepositoryInterface::class, EloquentVendorRepository::class);
    }

    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
