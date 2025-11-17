<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbAuditLogRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\AuditLogger\Contracts\AuditConfigInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use App\Services\AuditConfig;

/**
 * Service Provider for AuditLogger package
 * Satisfies: ARC-AUD-0009 (IoC container bindings in application service provider)
 *
 * @package App\Providers
 */
class AuditLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind repository interface to Eloquent implementation
        $this->app->bind(
            AuditLogRepositoryInterface::class,
            DbAuditLogRepository::class
        );

        // Bind config interface to implementation
        $this->app->bind(
            AuditConfigInterface::class,
            AuditConfig::class
        );

        // Register package services as singletons
        $this->app->singleton(\Nexus\AuditLogger\Services\AuditLogManager::class);
        $this->app->singleton(\Nexus\AuditLogger\Services\AuditLogSearchService::class);
        $this->app->singleton(\Nexus\AuditLogger\Services\AuditLogExportService::class);
        $this->app->singleton(\Nexus\AuditLogger\Services\RetentionPolicyService::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../../config/audit.php' => config_path('audit.php'),
        ], 'audit-config');

        // Register scheduled task for purging expired logs
        // Satisfies: BUS-AUD-0151
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\PurgeExpiredAuditLogsCommand::class,
            ]);
        }
    }
}
