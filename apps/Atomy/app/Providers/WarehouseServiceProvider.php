<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Warehouse\DbBinLocationRepository;
use App\Repositories\Warehouse\DbWarehouseRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;
use Nexus\Warehouse\Contracts\PickingOptimizerInterface;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\Warehouse\Services\PickingOptimizer;
use Nexus\Warehouse\Services\WarehouseManager;

final class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->singleton(WarehouseRepositoryInterface::class, DbWarehouseRepository::class);
        $this->app->singleton(BinLocationRepositoryInterface::class, DbBinLocationRepository::class);

        // Services
        $this->app->singleton(WarehouseManagerInterface::class, WarehouseManager::class);
        $this->app->singleton(PickingOptimizerInterface::class, PickingOptimizer::class);
    }
}
