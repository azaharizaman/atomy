<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\TransferManagerInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;
use Nexus\Backoffice\Services\BackofficeManager;
use Nexus\Backoffice\Services\TransferManager;
use App\Repositories\DbCompanyRepository;
use App\Repositories\DbDepartmentRepository;
use App\Repositories\DbOfficeRepository;
use App\Repositories\DbStaffRepository;
use App\Repositories\DbTransferRepository;
use App\Repositories\DbUnitRepository;

class BackofficeServiceProvider extends ServiceProvider
{
    /**
     * Register Backoffice services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->singleton(CompanyRepositoryInterface::class, DbCompanyRepository::class);
        $this->app->singleton(OfficeRepositoryInterface::class, DbOfficeRepository::class);
        $this->app->singleton(DepartmentRepositoryInterface::class, DbDepartmentRepository::class);
        $this->app->singleton(StaffRepositoryInterface::class, DbStaffRepository::class);
        $this->app->singleton(UnitRepositoryInterface::class, DbUnitRepository::class);
        $this->app->singleton(TransferRepositoryInterface::class, DbTransferRepository::class);

        // Register service bindings
        $this->app->singleton(BackofficeManagerInterface::class, BackofficeManager::class);
        $this->app->singleton(TransferManagerInterface::class, TransferManager::class);
    }

    /**
     * Bootstrap Backoffice services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(base_path('routes/api_backoffice.php'));

        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/backoffice.php' => config_path('backoffice.php'),
        ], 'backoffice-config');
    }
}
