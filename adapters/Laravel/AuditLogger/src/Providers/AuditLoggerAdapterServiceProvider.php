<?php

declare(strict_types=1);

namespace Nexus\Laravel\AuditLogger\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Nexus\Laravel\AuditLogger\Adapters\AuditLogRepositoryAdapter;

/**
 * Laravel Service Provider for AuditLogger package adapters.
 */
class AuditLoggerAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repository adapter
        $this->app->singleton(AuditLogRepositoryInterface::class, function ($app) {
            return new AuditLogRepositoryAdapter(
                logger: $app['log']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/auditlogger-adapter.php' => config_path('auditlogger-adapter.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            AuditLogRepositoryInterface::class,
        ];
    }
}
