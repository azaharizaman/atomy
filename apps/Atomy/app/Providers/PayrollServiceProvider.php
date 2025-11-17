<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Payroll\Contracts\ComponentRepositoryInterface;
use Nexus\Payroll\Contracts\EmployeeComponentRepositoryInterface;
use Nexus\Payroll\Contracts\PayslipRepositoryInterface;
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\Payroll\Services\PayrollEngine;
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\Services\PayslipManager;
use App\Repositories\Payroll\EloquentComponentRepository;
use App\Repositories\Payroll\EloquentPayslipRepository;
use App\Services\Payroll\TenantAwareStatutoryCalculator;

/**
 * Payroll Service Provider
 *
 * Registers and binds all Payroll package services in Laravel's IoC container.
 */
class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to concrete implementations
        $this->app->singleton(ComponentRepositoryInterface::class, EloquentComponentRepository::class);
        $this->app->singleton(PayslipRepositoryInterface::class, EloquentPayslipRepository::class);
        // Note: EmployeeComponentRepositoryInterface binding would go here

        // Bind StatutoryCalculatorInterface to tenant-aware implementation
        // This implementation will load the correct country-specific calculator
        // based on tenant configuration (e.g., Malaysia EPF/SOCSO/EIS/PCB, Singapore CPF/SDL)
        $this->app->singleton(StatutoryCalculatorInterface::class, TenantAwareStatutoryCalculator::class);

        // Bind service managers (auto-resolvable via constructor injection)
        $this->app->singleton(PayrollEngine::class);
        $this->app->singleton(ComponentManager::class);
        $this->app->singleton(PayslipManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Migrations are already in database/migrations
        // Routes will be loaded in RouteServiceProvider
    }
}
