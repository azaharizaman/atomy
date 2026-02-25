<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\LoggingAdminCreatorAdapter;
use App\Adapters\LoggingAuditLoggerAdapter;
use App\Adapters\LoggingCompanyCreatorAdapter;
use App\Adapters\LoggingFeatureConfiguratorAdapter;
use App\Adapters\LoggingFeatureQueryAdapter;
use App\Adapters\LoggingFeatureToggleAdapter;
use App\Adapters\LoggingSettingsInitializerAdapter;
use App\Adapters\LoggingSettingsQueryAdapter;
use App\Adapters\LoggingTenantCreatorAdapter;
use App\Adapters\LoggingTenantQueryAdapter;
use App\Adapters\LoggingTenantStatusAdapter;
use Illuminate\Support\ServiceProvider;
use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;
use Nexus\TenantOperations\Contracts\AuditLoggerAdapterInterface;
use Nexus\TenantOperations\Contracts\CompanyCreatorAdapterInterface;
use Nexus\TenantOperations\Contracts\FeatureConfiguratorAdapterInterface;
use Nexus\TenantOperations\Contracts\SettingsInitializerAdapterInterface;
use Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface;
use Nexus\TenantOperations\Contracts\TenantOnboardingCoordinatorInterface;
use Nexus\TenantOperations\Coordinators\TenantOnboardingCoordinator;
use Nexus\TenantOperations\DataProviders\FeatureQueryInterface;
use Nexus\TenantOperations\DataProviders\FeatureToggleInterface;
use Nexus\TenantOperations\DataProviders\SettingsQueryInterface;
use Nexus\TenantOperations\DataProviders\TenantQueryInterface;
use Nexus\TenantOperations\DataProviders\TenantStatusQueryInterface;
use Nexus\TenantOperations\DataProviders\ConfigurationQueryInterface;
use Nexus\TenantOperations\Rules\TenantCodeCheckerInterface;
use Nexus\TenantOperations\Rules\TenantDomainCheckerInterface;
use Nexus\TenantOperations\Services\TenantReadinessChecker;
use Psr\Log\LoggerInterface;

final class NexusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Adapters
        $this->app->singleton(TenantCreatorAdapterInterface::class, LoggingTenantCreatorAdapter::class);
        $this->app->singleton(AdminCreatorAdapterInterface::class, LoggingAdminCreatorAdapter::class);
        $this->app->singleton(CompanyCreatorAdapterInterface::class, LoggingCompanyCreatorAdapter::class);
        $this->app->singleton(AuditLoggerAdapterInterface::class, LoggingAuditLoggerAdapter::class);
        $this->app->singleton(SettingsInitializerAdapterInterface::class, LoggingSettingsInitializerAdapter::class);
        $this->app->singleton(FeatureConfiguratorAdapterInterface::class, LoggingFeatureConfiguratorAdapter::class);
        
        $this->app->singleton(TenantQueryInterface::class, LoggingTenantQueryAdapter::class);
        $this->app->singleton(TenantCodeCheckerInterface::class, LoggingTenantQueryAdapter::class);
        $this->app->singleton(TenantDomainCheckerInterface::class, LoggingTenantQueryAdapter::class);
        
        $this->app->singleton(SettingsQueryInterface::class, LoggingSettingsQueryAdapter::class);
        $this->app->singleton(FeatureQueryInterface::class, LoggingFeatureQueryAdapter::class);
        $this->app->singleton(FeatureToggleInterface::class, LoggingFeatureToggleAdapter::class);
        
        $this->app->singleton(TenantStatusQueryInterface::class, LoggingTenantStatusAdapter::class);
        $this->app->singleton(ConfigurationQueryInterface::class, LoggingTenantStatusAdapter::class);

        // Bind Orchestrator Services
        $this->app->singleton(TenantOnboardingCoordinatorInterface::class, TenantOnboardingCoordinator::class);
        
        $this->app->singleton(TenantReadinessChecker::class, function ($app) {
            return new TenantReadinessChecker(
                requiredAdapters: [
                    TenantCreatorAdapterInterface::class => $app->make(TenantCreatorAdapterInterface::class),
                    AdminCreatorAdapterInterface::class => $app->make(AdminCreatorAdapterInterface::class),
                    CompanyCreatorAdapterInterface::class => $app->make(CompanyCreatorAdapterInterface::class),
                    AuditLoggerAdapterInterface::class => $app->make(AuditLoggerAdapterInterface::class),
                    SettingsInitializerAdapterInterface::class => $app->make(SettingsInitializerAdapterInterface::class),
                    FeatureConfiguratorAdapterInterface::class => $app->make(FeatureConfiguratorAdapterInterface::class),
                ],
                logger: $app->make(LoggerInterface::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
