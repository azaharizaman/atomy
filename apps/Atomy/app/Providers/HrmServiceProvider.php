<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Hrm\Contracts\EmployeeRepositoryInterface;
use Nexus\Hrm\Contracts\ContractRepositoryInterface;
use Nexus\Hrm\Contracts\LeaveRepositoryInterface;
use Nexus\Hrm\Contracts\LeaveTypeRepositoryInterface;
use Nexus\Hrm\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Hrm\Contracts\AttendanceRepositoryInterface;
use Nexus\Hrm\Contracts\PerformanceReviewRepositoryInterface;
use Nexus\Hrm\Contracts\DisciplinaryRepositoryInterface;
use Nexus\Hrm\Contracts\TrainingRepositoryInterface;
use Nexus\Hrm\Contracts\TrainingEnrollmentRepositoryInterface;
use Nexus\Hrm\Contracts\OrganizationServiceContract;
use Nexus\Hrm\Services\EmployeeManager;
use Nexus\Hrm\Services\LeaveManager;
use Nexus\Hrm\Services\AttendanceManager;
use Nexus\Hrm\Services\PerformanceReviewManager;
use Nexus\Hrm\Services\DisciplinaryManager;
use Nexus\Hrm\Services\TrainingManager;
use App\Repositories\Hrm\EloquentEmployeeRepository;
use App\Repositories\Hrm\EloquentLeaveRepository;
use App\Services\Hrm\BackofficeOrganizationService;

/**
 * HRM Service Provider
 *
 * Registers and binds all HRM package services in Laravel's IoC container.
 */
class HrmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to concrete implementations
        $this->app->singleton(EmployeeRepositoryInterface::class, EloquentEmployeeRepository::class);
        $this->app->singleton(LeaveRepositoryInterface::class, EloquentLeaveRepository::class);
        // Note: Additional repository bindings would go here for:
        // ContractRepositoryInterface, LeaveTypeRepositoryInterface, LeaveBalanceRepositoryInterface,
        // AttendanceRepositoryInterface, PerformanceReviewRepositoryInterface,
        // DisciplinaryRepositoryInterface, TrainingRepositoryInterface, TrainingEnrollmentRepositoryInterface

        // Bind organization service contract to Backoffice integration
        $this->app->singleton(OrganizationServiceContract::class, BackofficeOrganizationService::class);

        // Bind service managers (these are auto-resolvable via constructor injection)
        // The package services automatically receive their dependencies from the container
        $this->app->singleton(EmployeeManager::class);
        $this->app->singleton(LeaveManager::class);
        $this->app->singleton(AttendanceManager::class);
        $this->app->singleton(PerformanceReviewManager::class);
        $this->app->singleton(DisciplinaryManager::class);
        $this->app->singleton(TrainingManager::class);
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
