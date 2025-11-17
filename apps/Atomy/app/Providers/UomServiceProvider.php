<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Uom\Services\UomManager;
use Nexus\Uom\Services\UomValidationService;
use App\Repositories\DbUomRepository;

/**
 * Service provider for Unit of Measurement package.
 *
 * Binds all package interfaces to Laravel implementations.
 *
 * Requirements: ARC-UOM-0032
 */
class UomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind repository interface to Eloquent implementation
        $this->app->singleton(UomRepositoryInterface::class, DbUomRepository::class);

        // Bind validation service
        $this->app->singleton(UomValidationService::class, function ($app) {
            return new UomValidationService(
                $app->make(UomRepositoryInterface::class)
            );
        });

        // Bind conversion engine
        $this->app->singleton(UomConversionEngine::class, function ($app) {
            return new UomConversionEngine(
                $app->make(UomRepositoryInterface::class),
                $app->make(UomValidationService::class)
            );
        });

        // Bind UoM manager (main API)
        $this->app->singleton(UomManager::class, function ($app) {
            return new UomManager(
                $app->make(UomRepositoryInterface::class),
                $app->make(UomConversionEngine::class),
                $app->make(UomValidationService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publish config if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/uom.php' => config_path('uom.php'),
            ], 'uom-config');
        }
    }
}
