<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbAttributeRepository;
use App\Repositories\DbCategoryRepository;
use App\Repositories\DbProductTemplateRepository;
use App\Repositories\DbProductVariantRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Product\Contracts\AttributeRepositoryInterface;
use Nexus\Product\Contracts\CategoryRepositoryInterface;
use Nexus\Product\Contracts\ProductTemplateRepositoryInterface;
use Nexus\Product\Contracts\ProductVariantRepositoryInterface;
use Nexus\Product\Services\BarcodeService;
use Nexus\Product\Services\ProductManager;
use Nexus\Product\Services\SkuGenerator;
use Nexus\Product\Services\VariantGenerator;

/**
 * Product Service Provider
 *
 * Binds Nexus\Product package interfaces to Atomy implementations.
 */
class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to Eloquent implementations
        $this->app->singleton(
            CategoryRepositoryInterface::class,
            DbCategoryRepository::class
        );

        $this->app->singleton(
            ProductTemplateRepositoryInterface::class,
            DbProductTemplateRepository::class
        );

        $this->app->singleton(
            ProductVariantRepositoryInterface::class,
            DbProductVariantRepository::class
        );

        $this->app->singleton(
            AttributeRepositoryInterface::class,
            DbAttributeRepository::class
        );

        // Bind services (dependencies auto-resolved)
        $this->app->singleton(SkuGenerator::class);
        $this->app->singleton(BarcodeService::class);
        $this->app->singleton(VariantGenerator::class);
        $this->app->singleton(ProductManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
