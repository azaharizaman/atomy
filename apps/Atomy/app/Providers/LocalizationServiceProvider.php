<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\CachedLocaleRepository;
use App\Repositories\DbLocaleRepository;
use App\Repositories\DbLocaleResolver;
use App\Repositories\DbTranslationRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Localization\Contracts\LocaleRepositoryInterface;
use Nexus\Localization\Contracts\LocaleResolverInterface;
use Nexus\Localization\Contracts\TranslationRepositoryInterface;
use Nexus\Localization\Services\CurrencyFormatter;
use Nexus\Localization\Services\DateTimeFormatter;
use Nexus\Localization\Services\LocalizationManager;
use Nexus\Localization\Services\NumberFormatter;
use Nexus\Localization\Services\TimezoneConverter;

/**
 * Localization service provider.
 *
 * Registers all localization service bindings and publishes configuration.
 */
class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/localization.php',
            'localization'
        );

        // Base repository (database)
        $this->app->singleton(DbLocaleRepository::class);

        // Cached repository decorator
        $this->app->singleton(CachedLocaleRepository::class, function ($app) {
            return new CachedLocaleRepository(
                $app->make(DbLocaleRepository::class)
            );
        });

        // Bind interface to cached repository
        $this->app->singleton(
            LocaleRepositoryInterface::class,
            CachedLocaleRepository::class
        );

        // Locale resolver
        $this->app->singleton(
            LocaleResolverInterface::class,
            DbLocaleResolver::class
        );

        // Translation repository (Phase 2 stub)
        $this->app->singleton(
            TranslationRepositoryInterface::class,
            DbTranslationRepository::class
        );

        // Core services
        $this->app->singleton(NumberFormatter::class);
        $this->app->singleton(DateTimeFormatter::class);
        $this->app->singleton(TimezoneConverter::class);
        $this->app->singleton(CurrencyFormatter::class);
        $this->app->singleton(LocalizationManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/localization.php' => config_path('localization.php'),
            ], 'localization-config');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
