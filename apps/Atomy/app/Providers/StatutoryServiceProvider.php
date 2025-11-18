<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbStatutoryReportRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Statutory\Adapters\DefaultAccountingAdapter;
use Nexus\Statutory\Adapters\DefaultPayrollStatutoryAdapter;
use Nexus\Statutory\Contracts\PayrollStatutoryInterface;
use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Core\Engine\FinanceDataExtractor;
use Nexus\Statutory\Core\Engine\FormatConverter;
use Nexus\Statutory\Core\Engine\ReportGenerator;
use Nexus\Statutory\Core\Engine\SchemaValidator;
use Nexus\Statutory\Services\StatutoryReportManager;

/**
 * Service Provider for Nexus\Statutory package.
 * 
 * Binds all Statutory interfaces to their concrete implementations.
 */
final class StatutoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(
            StatutoryReportRepositoryInterface::class,
            DbStatutoryReportRepository::class
        );

        // Default Adapter bindings
        // These are safe defaults that can be overridden by feature-specific adapters
        $this->app->singleton(
            TaxonomyReportGeneratorInterface::class,
            DefaultAccountingAdapter::class
        );

        $this->app->singleton(
            PayrollStatutoryInterface::class,
            DefaultPayrollStatutoryAdapter::class
        );

        // Core Engine bindings
        $this->app->singleton(SchemaValidator::class);
        $this->app->singleton(ReportGenerator::class);
        $this->app->singleton(FormatConverter::class);
        $this->app->singleton(FinanceDataExtractor::class);

        // Service bindings
        $this->app->singleton(StatutoryReportManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No bootstrap logic required
    }
}
