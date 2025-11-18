<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbComplianceSchemeRepository;
use App\Repositories\DbSodRuleRepository;
use App\Repositories\DbSodViolationRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface;
use Nexus\Compliance\Contracts\SodRuleRepositoryInterface;
use Nexus\Compliance\Contracts\SodViolationRepositoryInterface;
use Nexus\Compliance\Core\Contracts\RuleEngineInterface;
use Nexus\Compliance\Core\Engine\ConfigurationValidator;
use Nexus\Compliance\Core\Engine\RuleEngine;
use Nexus\Compliance\Core\Engine\SodValidator;
use Nexus\Compliance\Core\Engine\ValidationPipeline;
use Nexus\Compliance\Services\ComplianceManager;
use Nexus\Compliance\Services\ConfigurationAuditor;
use Nexus\Compliance\Services\SodManager;

/**
 * Service Provider for Nexus\Compliance package.
 * 
 * Binds all Compliance interfaces to their concrete implementations.
 */
final class ComplianceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(
            ComplianceSchemeRepositoryInterface::class,
            DbComplianceSchemeRepository::class
        );

        $this->app->singleton(
            SodRuleRepositoryInterface::class,
            DbSodRuleRepository::class
        );

        $this->app->singleton(
            SodViolationRepositoryInterface::class,
            DbSodViolationRepository::class
        );

        // Core Engine bindings
        $this->app->singleton(RuleEngineInterface::class, RuleEngine::class);
        $this->app->singleton(ValidationPipeline::class);
        $this->app->singleton(SodValidator::class);
        $this->app->singleton(ConfigurationValidator::class);

        // Service bindings
        $this->app->singleton(ComplianceManager::class);
        $this->app->singleton(SodManager::class);
        $this->app->singleton(ConfigurationAuditor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No bootstrap logic required
    }
}
