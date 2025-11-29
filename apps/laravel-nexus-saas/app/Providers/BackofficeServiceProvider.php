<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\Persistence\CompanyPersistenceInterface;
use Nexus\Backoffice\Contracts\Query\CompanyQueryInterface;
use Nexus\Backoffice\Contracts\Validation\CompanyValidationInterface;
use App\Repositories\Backoffice\EloquentCompanyRepository;

use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;
use Nexus\Backoffice\Contracts\Persistence\OfficePersistenceInterface;
use Nexus\Backoffice\Contracts\Query\OfficeQueryInterface;
use Nexus\Backoffice\Contracts\Validation\OfficeValidationInterface;
use App\Repositories\Backoffice\EloquentOfficeRepository;

use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\Persistence\DepartmentPersistenceInterface;
use Nexus\Backoffice\Contracts\Query\DepartmentQueryInterface;
use Nexus\Backoffice\Contracts\Validation\DepartmentValidationInterface;
use App\Repositories\Backoffice\EloquentDepartmentRepository;

use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\Persistence\StaffPersistenceInterface;
use Nexus\Backoffice\Contracts\Query\StaffQueryInterface;
use Nexus\Backoffice\Contracts\Validation\StaffValidationInterface;
use App\Repositories\Backoffice\EloquentStaffRepository;

use Nexus\Backoffice\Contracts\UnitRepositoryInterface;
use App\Repositories\Backoffice\EloquentUnitRepository;

use Nexus\Backoffice\Contracts\TransferRepositoryInterface;
use App\Repositories\Backoffice\EloquentTransferRepository;
use Nexus\Backoffice\Contracts\TransferManagerInterface;
use Nexus\Backoffice\Services\TransferManager;

class BackofficeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Company
        $this->app->bind(CompanyRepositoryInterface::class, EloquentCompanyRepository::class);
        $this->app->bind(CompanyPersistenceInterface::class, EloquentCompanyRepository::class);
        $this->app->bind(CompanyQueryInterface::class, EloquentCompanyRepository::class);
        $this->app->bind(CompanyValidationInterface::class, EloquentCompanyRepository::class);

        // Office
        $this->app->bind(OfficeRepositoryInterface::class, EloquentOfficeRepository::class);
        $this->app->bind(OfficePersistenceInterface::class, EloquentOfficeRepository::class);
        $this->app->bind(OfficeQueryInterface::class, EloquentOfficeRepository::class);
        $this->app->bind(OfficeValidationInterface::class, EloquentOfficeRepository::class);

        // Department
        $this->app->bind(DepartmentRepositoryInterface::class, EloquentDepartmentRepository::class);
        $this->app->bind(DepartmentPersistenceInterface::class, EloquentDepartmentRepository::class);
        $this->app->bind(DepartmentQueryInterface::class, EloquentDepartmentRepository::class);
        $this->app->bind(DepartmentValidationInterface::class, EloquentDepartmentRepository::class);

        // Staff
        $this->app->bind(StaffRepositoryInterface::class, EloquentStaffRepository::class);
        $this->app->bind(StaffPersistenceInterface::class, EloquentStaffRepository::class);
        $this->app->bind(StaffQueryInterface::class, EloquentStaffRepository::class);
        $this->app->bind(StaffValidationInterface::class, EloquentStaffRepository::class);

        // Unit
        $this->app->bind(UnitRepositoryInterface::class, EloquentUnitRepository::class);

        // Transfer
        $this->app->bind(TransferRepositoryInterface::class, EloquentTransferRepository::class);
        $this->app->bind(TransferManagerInterface::class, TransferManager::class);

        // Manager
        $this->app->bind(\Nexus\Backoffice\Contracts\BackofficeManagerInterface::class, \Nexus\Backoffice\Services\BackofficeManager::class);
    }

    public function boot(): void
    {
        //
    }
}
