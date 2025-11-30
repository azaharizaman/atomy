<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Nexus\Inventory\Contracts\InventoryAnalyticsRepositoryInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Contracts\StockMovementRepositoryInterface;
use Nexus\Laravel\Inventory\Repositories\EloquentInventoryAnalyticsRepository;
use Nexus\Laravel\Inventory\Repositories\EloquentStockLevelRepository;
use Nexus\Laravel\Inventory\Repositories\EloquentStockMovementRepository;

/**
 * Laravel Service Provider for Nexus Inventory Package
 * 
 * Registers Eloquent implementations of Inventory repository interfaces.
 * This provider bridges the framework-agnostic Inventory package with Laravel.
 */
class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register Eloquent repository implementations
        $this->app->bind(
            StockLevelRepositoryInterface::class,
            EloquentStockLevelRepository::class
        );

        $this->app->bind(
            StockMovementRepositoryInterface::class,
            EloquentStockMovementRepository::class
        );

        $this->app->bind(
            InventoryAnalyticsRepositoryInterface::class,
            EloquentInventoryAnalyticsRepository::class
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish migrations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Migrations' => database_path('migrations'),
            ], 'nexus-inventory-migrations');
        }
    }
}
