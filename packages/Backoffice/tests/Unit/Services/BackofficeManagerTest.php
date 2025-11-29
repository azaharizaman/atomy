<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Services;

use Nexus\Backoffice\Contracts\CompanyInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\DepartmentInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\UnitInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;
use Nexus\Backoffice\Exceptions\CircularReferenceException;
use Nexus\Backoffice\Exceptions\CompanyNotFoundException;
use Nexus\Backoffice\Exceptions\DepartmentNotFoundException;
use Nexus\Backoffice\Exceptions\DuplicateCodeException;
use Nexus\Backoffice\Exceptions\InvalidHierarchyException;
use Nexus\Backoffice\Exceptions\InvalidOperationException;
use Nexus\Backoffice\Exceptions\OfficeNotFoundException;
use Nexus\Backoffice\Exceptions\StaffNotFoundException;
use Nexus\Backoffice\Exceptions\UnitNotFoundException;
use Nexus\Backoffice\Services\BackofficeManager;
use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\CompanyStatus;
use Nexus\Backoffice\ValueObjects\DepartmentStatus;
use Nexus\Backoffice\ValueObjects\OfficeStatus;
use Nexus\Backoffice\ValueObjects\OfficeType;
use Nexus\Backoffice\ValueObjects\StaffStatus;
use Nexus\Backoffice\ValueObjects\UnitStatus;

/**
 * Unit tests for BackofficeManager service.
 */
class BackofficeManagerTest extends TestCase
{
    private CompanyRepositoryInterface $companyRepository;
    private OfficeRepositoryInterface $officeRepository;
    private DepartmentRepositoryInterface $departmentRepository;
    private StaffRepositoryInterface $staffRepository;
    private UnitRepositoryInterface $unitRepository;
    private BackofficeManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->officeRepository = $this->createMock(OfficeRepositoryInterface::class);
        $this->departmentRepository = $this->createMock(DepartmentRepositoryInterface::class);
        $this->staffRepository = $this->createMock(StaffRepositoryInterface::class);
        $this->unitRepository = $this->createMock(UnitRepositoryInterface::class);

        $this->manager = new BackofficeManager(
            $this->companyRepository,
            $this->officeRepository,
            $this->departmentRepository,
            $this->staffRepository,
            $this->unitRepository
        );
    }

    // =========================================================================
    // Company Operations Tests
    // =========================================================================

    public function test_create_company_with_valid_data(): void
    {
        $companyData = [
            'code' => 'COMP-001',
            'name' => 'Test Company',
            'status' => CompanyStatus::ACTIVE->value,
        ];

        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');
        $company->method('getCode')->willReturn('COMP-001');
        $company->method('getName')->willReturn('Test Company');

        $this->companyRepository
            ->method('codeExists')
            ->with('COMP-001')
            ->willReturn(false);

        $this->companyRepository
            ->method('save')
            ->with($companyData)
            ->willReturn($company);

        $result = $this->manager->createCompany($companyData);

        $this->assertInstanceOf(CompanyInterface::class, $result);
        $this->assertSame('company-123', $result->getId());
        $this->assertSame('COMP-001', $result->getCode());
    }

    public function test_create_company_throws_exception_for_duplicate_code(): void
    {
        $this->companyRepository
            ->method('codeExists')
            ->with('COMP-001')
            ->willReturn(true);

        $this->expectException(DuplicateCodeException::class);

        $this->manager->createCompany([
            'code' => 'COMP-001',
            'name' => 'Test Company',
        ]);
    }

    public function test_create_company_with_parent_validates_parent_exists(): void
    {
        $this->companyRepository
            ->method('codeExists')
            ->willReturn(false);

        $this->companyRepository
            ->method('findById')
            ->with('parent-company-id')
            ->willReturn(null);

        $this->expectException(CompanyNotFoundException::class);

        $this->manager->createCompany([
            'code' => 'COMP-001',
            'name' => 'Subsidiary Company',
            'parent_company_id' => 'parent-company-id',
        ]);
    }

    public function test_create_company_with_parent_validates_parent_is_active(): void
    {
        $parentCompany = $this->createMock(CompanyInterface::class);
        $parentCompany->method('getId')->willReturn('parent-company-id');
        $parentCompany->method('getStatus')->willReturn(CompanyStatus::INACTIVE->value);
        $parentCompany->method('isActive')->willReturn(false);

        $this->companyRepository
            ->method('codeExists')
            ->willReturn(false);

        $this->companyRepository
            ->method('findById')
            ->with('parent-company-id')
            ->willReturn($parentCompany);

        $this->expectException(InvalidOperationException::class);

        $this->manager->createCompany([
            'code' => 'COMP-001',
            'name' => 'Subsidiary Company',
            'parent_company_id' => 'parent-company-id',
        ]);
    }

    public function test_get_company_returns_company(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $result = $this->manager->getCompany('company-123');

        $this->assertInstanceOf(CompanyInterface::class, $result);
        $this->assertSame('company-123', $result->getId());
    }

    public function test_get_company_returns_null_when_not_found(): void
    {
        $this->companyRepository
            ->method('findById')
            ->with('nonexistent-id')
            ->willReturn(null);

        $result = $this->manager->getCompany('nonexistent-id');

        $this->assertNull($result);
    }

    public function test_update_company_with_valid_data(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');
        $company->method('getCode')->willReturn('COMP-001');

        $updatedCompany = $this->createMock(CompanyInterface::class);
        $updatedCompany->method('getId')->willReturn('company-123');
        $updatedCompany->method('getName')->willReturn('Updated Company Name');

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $this->companyRepository
            ->method('codeExists')
            ->willReturn(false);

        $this->companyRepository
            ->method('update')
            ->willReturn($updatedCompany);

        $result = $this->manager->updateCompany('company-123', [
            'name' => 'Updated Company Name',
        ]);

        $this->assertSame('Updated Company Name', $result->getName());
    }

    public function test_delete_company_with_no_dependencies(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        // No subsidiaries
        $this->companyRepository
            ->method('getSubsidiaries')
            ->with('company-123')
            ->willReturn([]);

        // No offices (so no staff)
        $this->officeRepository
            ->method('getByCompany')
            ->with('company-123')
            ->willReturn([]);

        $this->companyRepository
            ->expects($this->once())
            ->method('delete')
            ->with('company-123')
            ->willReturn(true);

        $result = $this->manager->deleteCompany('company-123');

        $this->assertTrue($result);
    }

    public function test_delete_company_throws_exception_when_has_active_subsidiaries(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');

        $activeSubsidiary = $this->createMock(CompanyInterface::class);
        $activeSubsidiary->method('getId')->willReturn('subsidiary-123');
        $activeSubsidiary->method('getStatus')->willReturn(CompanyStatus::ACTIVE->value);

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $this->companyRepository
            ->method('getSubsidiaries')
            ->with('company-123')
            ->willReturn([$activeSubsidiary]);

        $this->expectException(InvalidOperationException::class);

        $this->manager->deleteCompany('company-123');
    }

    // =========================================================================
    // Office Operations Tests
    // =========================================================================

    public function test_create_office_with_valid_data(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');
        $company->method('isActive')->willReturn(true);
        $company->method('getStatus')->willReturn(CompanyStatus::ACTIVE->value);

        $officeData = [
            'code' => 'OFF-001',
            'name' => 'Head Office',
            'company_id' => 'company-123',
            'type' => OfficeType::HEAD_OFFICE->value,
            'status' => OfficeStatus::ACTIVE->value,
            'country' => 'MY',
            'postal_code' => '50000',
        ];

        $office = $this->createMock(OfficeInterface::class);
        $office->method('getId')->willReturn('office-123');
        $office->method('getCode')->willReturn('OFF-001');

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $this->officeRepository
            ->method('codeExists')
            ->willReturn(false);

        $this->officeRepository
            ->method('hasHeadOffice')
            ->willReturn(false);

        $this->officeRepository
            ->method('save')
            ->willReturn($office);

        $result = $this->manager->createOffice($officeData);

        $this->assertInstanceOf(OfficeInterface::class, $result);
        $this->assertSame('office-123', $result->getId());
    }

    public function test_create_office_throws_exception_for_inactive_company(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');
        $company->method('isActive')->willReturn(false);
        $company->method('getStatus')->willReturn(CompanyStatus::INACTIVE->value);

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $this->expectException(InvalidOperationException::class);

        $this->manager->createOffice([
            'code' => 'OFF-001',
            'name' => 'Branch Office',
            'company_id' => 'company-123',
            'country' => 'MY',
            'postal_code' => '50000',
        ]);
    }

    public function test_get_office_returns_office(): void
    {
        $office = $this->createMock(OfficeInterface::class);
        $office->method('getId')->willReturn('office-123');

        $this->officeRepository
            ->method('findById')
            ->with('office-123')
            ->willReturn($office);

        $result = $this->manager->getOffice('office-123');

        $this->assertInstanceOf(OfficeInterface::class, $result);
        $this->assertSame('office-123', $result->getId());
    }

    public function test_get_office_returns_null_when_not_found(): void
    {
        $this->officeRepository
            ->method('findById')
            ->with('nonexistent-id')
            ->willReturn(null);

        $result = $this->manager->getOffice('nonexistent-id');

        $this->assertNull($result);
    }

    // =========================================================================
    // Department Operations Tests
    // =========================================================================

    public function test_create_department_with_valid_data(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');
        $company->method('isActive')->willReturn(true);
        $company->method('getStatus')->willReturn(CompanyStatus::ACTIVE->value);

        $departmentData = [
            'code' => 'DEPT-001',
            'name' => 'Engineering',
            'company_id' => 'company-123',
            'status' => DepartmentStatus::ACTIVE->value,
        ];

        $department = $this->createMock(DepartmentInterface::class);
        $department->method('getId')->willReturn('dept-123');
        $department->method('getCode')->willReturn('DEPT-001');

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $this->departmentRepository
            ->method('codeExists')
            ->willReturn(false);

        $this->departmentRepository
            ->method('save')
            ->willReturn($department);

        $result = $this->manager->createDepartment($departmentData);

        $this->assertInstanceOf(DepartmentInterface::class, $result);
        $this->assertSame('dept-123', $result->getId());
    }

    public function test_create_department_with_parent_validates_parent_exists(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');
        $company->method('isActive')->willReturn(true);
        $company->method('getStatus')->willReturn(CompanyStatus::ACTIVE->value);

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $this->departmentRepository
            ->method('codeExists')
            ->willReturn(false);

        $this->departmentRepository
            ->method('findById')
            ->with('parent-dept-id')
            ->willReturn(null);

        $this->expectException(DepartmentNotFoundException::class);

        $this->manager->createDepartment([
            'code' => 'DEPT-001',
            'name' => 'Sub-Department',
            'company_id' => 'company-123',
            'parent_department_id' => 'parent-dept-id',
        ]);
    }

    public function test_get_department_returns_department(): void
    {
        $department = $this->createMock(DepartmentInterface::class);
        $department->method('getId')->willReturn('dept-123');

        $this->departmentRepository
            ->method('findById')
            ->with('dept-123')
            ->willReturn($department);

        $result = $this->manager->getDepartment('dept-123');

        $this->assertInstanceOf(DepartmentInterface::class, $result);
        $this->assertSame('dept-123', $result->getId());
    }

    public function test_get_department_returns_null_when_not_found(): void
    {
        $this->departmentRepository
            ->method('findById')
            ->with('nonexistent-id')
            ->willReturn(null);

        $result = $this->manager->getDepartment('nonexistent-id');

        $this->assertNull($result);
    }

    public function test_delete_department_throws_exception_when_has_active_staff(): void
    {
        $department = $this->createMock(DepartmentInterface::class);
        $department->method('getId')->willReturn('dept-123');

        $this->departmentRepository
            ->method('findById')
            ->with('dept-123')
            ->willReturn($department);

        // No sub-departments
        $this->departmentRepository
            ->method('getSubDepartments')
            ->with('dept-123')
            ->willReturn([]);

        // But has active staff
        $activeStaff = $this->createMock(StaffInterface::class);
        $activeStaff->method('getStatus')->willReturn(StaffStatus::ACTIVE->value);

        $this->staffRepository
            ->method('getByDepartment')
            ->with('dept-123')
            ->willReturn([$activeStaff]);

        $this->expectException(InvalidOperationException::class);

        $this->manager->deleteDepartment('dept-123');
    }

    public function test_delete_department_throws_exception_when_has_sub_departments(): void
    {
        $department = $this->createMock(DepartmentInterface::class);
        $department->method('getId')->willReturn('dept-123');

        $this->departmentRepository
            ->method('findById')
            ->with('dept-123')
            ->willReturn($department);

        // Has sub-departments (checked first)
        $subDepartment = $this->createMock(DepartmentInterface::class);
        $this->departmentRepository
            ->method('getSubDepartments')
            ->with('dept-123')
            ->willReturn([$subDepartment]);

        $this->expectException(InvalidOperationException::class);

        $this->manager->deleteDepartment('dept-123');
    }

    // =========================================================================
    // Staff Operations Tests
    // =========================================================================

    public function test_create_staff_with_valid_data(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');
        $company->method('isActive')->willReturn(true);
        $company->method('getStatus')->willReturn(CompanyStatus::ACTIVE->value);

        $staffData = [
            'employee_id' => 'EMP-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company_id' => 'company-123',
            'status' => StaffStatus::ACTIVE->value,
            'hire_date' => '2024-01-15',
        ];

        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');
        $staff->method('getEmployeeId')->willReturn('EMP-001');

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $this->staffRepository
            ->method('employeeIdExists')
            ->willReturn(false);

        $this->staffRepository
            ->method('save')
            ->willReturn($staff);

        $result = $this->manager->createStaff($staffData);

        $this->assertInstanceOf(StaffInterface::class, $result);
        $this->assertSame('staff-123', $result->getId());
    }

    public function test_get_staff_returns_staff(): void
    {
        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        $result = $this->manager->getStaff('staff-123');

        $this->assertInstanceOf(StaffInterface::class, $result);
        $this->assertSame('staff-123', $result->getId());
    }

    public function test_get_staff_returns_null_when_not_found(): void
    {
        $this->staffRepository
            ->method('findById')
            ->with('nonexistent-id')
            ->willReturn(null);

        $result = $this->manager->getStaff('nonexistent-id');

        $this->assertNull($result);
    }

    public function test_assign_staff_to_department(): void
    {
        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');
        $staff->method('getStatus')->willReturn(StaffStatus::ACTIVE->value);

        $department = $this->createMock(DepartmentInterface::class);
        $department->method('getId')->willReturn('dept-123');

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        $this->departmentRepository
            ->method('findById')
            ->with('dept-123')
            ->willReturn($department);

        // The method returns void, so we just verify it doesn't throw
        $this->manager->assignStaffToDepartment('staff-123', 'dept-123', 'member');

        // If we get here without exception, the test passed
        $this->assertTrue(true);
    }

    public function test_set_supervisor_validates_not_self_reference(): void
    {
        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        // The service throws InvalidArgumentException when trying to set staff as their own supervisor
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Staff cannot be their own supervisor');

        $this->manager->setSupervisor('staff-123', 'staff-123');
    }

    // =========================================================================
    // Unit Operations Tests
    // =========================================================================

    public function test_create_unit_with_valid_data(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn('company-123');
        $company->method('isActive')->willReturn(true);
        $company->method('getStatus')->willReturn(CompanyStatus::ACTIVE->value);

        $unitData = [
            'code' => 'UNIT-001',
            'name' => 'Project Team Alpha',
            'company_id' => 'company-123',
            'status' => UnitStatus::ACTIVE->value,
        ];

        $unit = $this->createMock(UnitInterface::class);
        $unit->method('getId')->willReturn('unit-123');
        $unit->method('getCode')->willReturn('UNIT-001');

        $this->companyRepository
            ->method('findById')
            ->with('company-123')
            ->willReturn($company);

        $this->unitRepository
            ->method('codeExists')
            ->willReturn(false);

        $this->unitRepository
            ->method('save')
            ->willReturn($unit);

        $result = $this->manager->createUnit($unitData);

        $this->assertInstanceOf(UnitInterface::class, $result);
        $this->assertSame('unit-123', $result->getId());
    }

    public function test_get_unit_returns_unit(): void
    {
        $unit = $this->createMock(UnitInterface::class);
        $unit->method('getId')->willReturn('unit-123');

        $this->unitRepository
            ->method('findById')
            ->with('unit-123')
            ->willReturn($unit);

        $result = $this->manager->getUnit('unit-123');

        $this->assertInstanceOf(UnitInterface::class, $result);
        $this->assertSame('unit-123', $result->getId());
    }

    public function test_get_unit_returns_null_when_not_found(): void
    {
        $this->unitRepository
            ->method('findById')
            ->with('nonexistent-id')
            ->willReturn(null);

        $result = $this->manager->getUnit('nonexistent-id');

        $this->assertNull($result);
    }

    public function test_add_unit_member(): void
    {
        $unit = $this->createMock(UnitInterface::class);
        $unit->method('getId')->willReturn('unit-123');

        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');
        $staff->method('getStatus')->willReturn(StaffStatus::ACTIVE->value);

        $this->unitRepository
            ->method('findById')
            ->with('unit-123')
            ->willReturn($unit);

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        $this->unitRepository
            ->expects($this->once())
            ->method('addMember')
            ->with('unit-123', 'staff-123', 'member');

        $this->manager->addUnitMember('unit-123', 'staff-123', 'member');
    }
}
