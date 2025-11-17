<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Storage\FlysystemDriver;
use App\Services\Storage\TemporaryUrlGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Nexus\Storage\Contracts\PublicUrlGeneratorInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;

/**
 * Storage Service Provider
 *
 * Registers and binds all Storage package services in Laravel's IoC container.
 * This provider bridges the framework-agnostic Nexus\Storage contracts with
 * Laravel's Filesystem implementation.
 */
class StorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/storage.php',
            'storage'
        );
        // Bind StorageDriverInterface to FlysystemDriver
        $this->app->singleton(StorageDriverInterface::class, function ($app) {
            $diskName = config('storage.default', 'local');
            $filesystem = Storage::disk($diskName);

            return new FlysystemDriver($filesystem);
        });

        // Bind PublicUrlGeneratorInterface to TemporaryUrlGenerator
        $this->app->singleton(PublicUrlGeneratorInterface::class, function ($app) {
            $diskName = config('storage.default', 'local');
            $filesystem = Storage::disk($diskName);

            return new TemporaryUrlGenerator($filesystem, $diskName);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        if (file_exists(__DIR__ . '/../../routes/api_storage.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api_storage.php');
        }

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/storage.php' => config_path('storage.php'),
        ], 'storage-config');
    }
}
