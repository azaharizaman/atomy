# Getting Started with Nexus Backoffice

## Prerequisites

- PHP 8.3 or higher
- Composer
- A persistence layer (database with support for nested sets)

## Installation

```bash
composer require nexus/backoffice:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ **Company Hierarchies** - Multi-level parent-subsidiary relationships
- ✅ **Office Management** - Physical location tracking across regions
- ✅ **Department Structures** - Hierarchical organizational units (up to 8 levels)
- ✅ **Staff Management** - Employee profiles, assignments, and supervisory chains
- ✅ **Matrix Organizations** - Cross-functional units/teams
- ✅ **Transfer Workflows** - Staff movement with approval processes
- ✅ **Organizational Reporting** - Headcount, turnover, span of control

Do NOT use this package for:
- ❌ **Time & Attendance** - Use `Nexus\Hrm` for timekeeping
- ❌ **Payroll Processing** - Use `Nexus\Payroll` for compensation
- ❌ **Performance Reviews** - Use `Nexus\Hrm` for performance management
- ❌ **Recruitment** - Use `Nexus\Hrm` for hiring workflows

## Core Concepts

### Concept 1: Company Hierarchy

Companies can form parent-subsidiary relationships for holding structures:

```
ABC Holdings (Parent)
├── ABC Manufacturing (Subsidiary)
├── ABC Services (Subsidiary)
└── ABC Technology
    └── ABC Digital (Nested Subsidiary)
```

**Key Rules:**
- Parent companies must be active to have active subsidiaries
- Circular references are prevented
- Companies can be dissolved while preserving history

### Concept 2: Office Management

Offices represent **physical locations** where work happens:

**Office Types:**
- **Head Office:** Corporate headquarters (one per company)
- **Branch:** Regional or local offices
- **Regional:** Coordination centers spanning multiple branches
- **Satellite:** Small remote offices
- **Virtual:** Remote-only locations

**Key Features:**
- Location data (address, country, timezone)
- Contact details (phone, email)
- Operating hours and capacity tracking
- Can exist independently of department structure

### Concept 3: Department Structure

Departments represent **functional units**, independent of physical locations:

**Department Types:**
- **Functional:** Organized by function (Finance, HR, IT)
- **Divisional:** Product/region-based divisions
- **Matrix:** Cross-functional collaboration
- **Project-Based:** Temporary organizational units

**Hierarchical Example:**
```
Finance Department
├── Accounting
│   ├── Accounts Payable
│   └── Accounts Receivable
├── Tax
└── Treasury
```

**Key Rules:**
- Maximum recommended depth: 8 levels
- Each department can have a department head (manager)
- Cost centers track financial allocations
- Cannot delete departments with active staff or sub-departments

### Concept 4: Staff Management

Staff represent **employees** with comprehensive profiles:

**Staff Types:**
- **Permanent:** Full-time permanent employees
- **Contract:** Fixed-term contracts
- **Temporary:** Short-term assignments
- **Intern:** Interns and trainees
- **Consultant:** External consultants

**Staff Status:**
- **Active:** Currently employed
- **Inactive:** Temporarily inactive (e.g., unpaid leave)
- **On Leave:** Extended leave
- **Terminated:** Employment ended

**Key Features:**
- Multiple department assignments (primary + secondary)
- Supervisory chains (manager-subordinate relationships)
- Position, grade, salary band tracking
- Skills, qualifications, competencies

### Concept 5: Unit/Matrix Organizations

Units enable **cross-functional teams** that transcend traditional hierarchies:

**Unit Types:**
- **Project Team:** Temporary project groups
- **Committee:** Decision-making bodies
- **Task Force:** Special initiative groups
- **Working Group:** Collaborative teams
- **Center of Excellence:** Knowledge hubs

**Member Roles:**
- **Leader:** Unit leader
- **Member:** Regular member
- **Secretary:** Administrative support
- **Advisor:** Advisory role

**Example:**
```
Digital Transformation Committee
├── Leader: CTO (from IT Department)
├── Members:
│   ├── Finance Manager (from Finance)
│   ├── Operations Manager (from Operations)
│   └── HR Manager (from HR)
└── Secretary: Executive Assistant
```

### Concept 6: Transfer Workflows

Staff transfers require approval and scheduling:

**Transfer Types:**
- **Promotion:** Upward movement with increased responsibilities
- **Lateral Move:** Horizontal movement, same level
- **Demotion:** Downward movement
- **Relocation:** Geographic transfer

**Transfer Status:**
- **Pending:** Awaiting approval
- **Approved:** Approved, not yet executed
- **Rejected:** Declined
- **Completed:** Transfer executed
- **Cancelled:** Request cancelled

**Key Rules:**
- Effective dates cannot be more than 30 days in the past
- Approval required from both source and destination
- Pending transfers block new transfer requests for the same staff
- Completed transfers can be rolled back

---

## Basic Configuration

### Step 1: Implement Entity Interfaces

Create concrete implementations for each entity type:

```php
<?php

namespace App\ValueObjects\Backoffice;

use Nexus\Backoffice\Contracts\CompanyInterface;

final readonly class Company implements CompanyInterface
{
    public function __construct(
        private string $id,
        private string $code,
        private string $name,
        private ?string $registrationNumber,
        private ?\DateTimeInterface $registrationDate,
        private ?string $jurisdiction,
        private string $status,
        private ?string $parentCompanyId,
        private ?int $financialYearStartMonth,
        private ?string $industry,
        private ?string $size,
        private ?string $taxId,
        private array $metadata,
        private \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function getJurisdiction(): ?string
    {
        return $this->jurisdiction;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getParentCompanyId(): ?string
    {
        return $this->parentCompanyId;
    }

    public function getFinancialYearStartMonth(): ?int
    {
        return $this->financialYearStartMonth;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

### Step 2: Implement Repository Interfaces

Implement all 6 repository interfaces:

```php
<?php

namespace App\Repositories\Backoffice;

use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\CompanyInterface;
use App\Models\Backoffice\Company as CompanyModel;
use App\ValueObjects\Backoffice\Company;

final readonly class DbCompanyRepository implements CompanyRepositoryInterface
{
    public function save(array $data): CompanyInterface
    {
        $model = CompanyModel::create([
            'id' => \Symfony\Component\Uid\Ulid::generate(),
            'code' => $data['code'],
            'name' => $data['name'],
            'registration_number' => $data['registration_number'] ?? null,
            'registration_date' => $data['registration_date'] ?? null,
            'jurisdiction' => $data['jurisdiction'] ?? null,
            'status' => $data['status'],
            'parent_company_id' => $data['parent_company_id'] ?? null,
            'financial_year_start_month' => $data['financial_year_start_month'] ?? null,
            'industry' => $data['industry'] ?? null,
            'size' => $data['size'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        return $this->modelToInterface($model);
    }

    public function update(string $id, array $data): CompanyInterface
    {
        $model = CompanyModel::findOrFail($id);
        $model->update($data);

        return $this->modelToInterface($model);
    }

    public function delete(string $id): bool
    {
        return CompanyModel::where('id', $id)->delete() > 0;
    }

    public function findById(string $id): ?CompanyInterface
    {
        $model = CompanyModel::find($id);

        return $model ? $this->modelToInterface($model) : null;
    }

    public function findByCode(string $code): ?CompanyInterface
    {
        $model = CompanyModel::where('code', $code)->first();

        return $model ? $this->modelToInterface($model) : null;
    }

    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $query = CompanyModel::where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function registrationNumberExists(string $number, ?string $excludeId = null): bool
    {
        $query = CompanyModel::where('registration_number', $number);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function hasCircularReference(string $companyId, string $parentId): bool
    {
        if ($companyId === 'new') {
            return false; // New companies cannot have circular references
        }

        $current = $parentId;
        $visited = [];

        while ($current) {
            if ($current === $companyId) {
                return true;
            }

            if (isset($visited[$current])) {
                break; // Prevent infinite loops
            }

            $visited[$current] = true;
            $parent = CompanyModel::find($current);
            $current = $parent?->parent_company_id;
        }

        return false;
    }

    public function getSubsidiaries(string $companyId): array
    {
        return CompanyModel::where('parent_company_id', $companyId)
            ->get()
            ->map(fn($m) => $this->modelToInterface($m))
            ->all();
    }

    public function getParentChain(string $companyId): array
    {
        $chain = [];
        $current = CompanyModel::find($companyId);

        while ($current && $current->parent_company_id) {
            $parent = CompanyModel::find($current->parent_company_id);
            if ($parent) {
                $chain[] = $this->modelToInterface($parent);
                $current = $parent;
            } else {
                break;
            }
        }

        return $chain;
    }

    public function findAll(): array
    {
        return CompanyModel::all()
            ->map(fn($m) => $this->modelToInterface($m))
            ->all();
    }

    private function modelToInterface(CompanyModel $model): CompanyInterface
    {
        return new Company(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            registrationNumber: $model->registration_number,
            registrationDate: $model->registration_date 
                ? new \DateTimeImmutable($model->registration_date) 
                : null,
            jurisdiction: $model->jurisdiction,
            status: $model->status,
            parentCompanyId: $model->parent_company_id,
            financialYearStartMonth: $model->financial_year_start_month,
            industry: $model->industry,
            size: $model->size,
            taxId: $model->tax_id,
            metadata: $model->metadata ?? [],
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: new \DateTimeImmutable($model->updated_at),
        );
    }
}
```

### Step 3: Bind in Service Provider

Register all interfaces in your application's service provider:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\TransferManagerInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;
use Nexus\Backoffice\Services\BackofficeManager;
use Nexus\Backoffice\Services\TransferManager;
use App\Repositories\Backoffice\DbCompanyRepository;
use App\Repositories\Backoffice\DbOfficeRepository;
use App\Repositories\Backoffice\DbDepartmentRepository;
use App\Repositories\Backoffice\DbStaffRepository;
use App\Repositories\Backoffice\DbUnitRepository;
use App\Repositories\Backoffice\DbTransferRepository;

final class BackofficeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(CompanyRepositoryInterface::class, DbCompanyRepository::class);
        $this->app->singleton(OfficeRepositoryInterface::class, DbOfficeRepository::class);
        $this->app->singleton(DepartmentRepositoryInterface::class, DbDepartmentRepository::class);
        $this->app->singleton(StaffRepositoryInterface::class, DbStaffRepository::class);
        $this->app->singleton(UnitRepositoryInterface::class, DbUnitRepository::class);
        $this->app->singleton(TransferRepositoryInterface::class, DbTransferRepository::class);

        // Bind services
        $this->app->singleton(BackofficeManagerInterface::class, BackofficeManager::class);
        $this->app->singleton(TransferManagerInterface::class, TransferManager::class);
    }

    public function boot(): void
    {
        //
    }
}
```

---

## First Integration: Complete Company Setup Example

### Step 1: Create a Company

```php
use Nexus\Backoffice\Services\BackofficeManager;

$manager = app(BackofficeManager::class);

// Create parent company
$parentCompany = $manager->createCompany([
    'code' => 'HOLDINGS',
    'name' => 'ABC Holdings Ltd',
    'registration_number' => '202301234567',
    'registration_date' => new \DateTime('2023-01-15'),
    'jurisdiction' => 'Malaysia',
    'status' => 'active',
    'financial_year_start_month' => 1, // January
    'industry' => 'Diversified Holdings',
    'size' => 'Large',
    'tax_id' => 'C1234567890',
    'metadata' => [
        'website' => 'https://abcholdings.com',
        'stock_symbol' => 'ABC',
    ],
]);

// Create subsidiary
$subsidiary = $manager->createCompany([
    'code' => 'MANUFACTURING',
    'name' => 'ABC Manufacturing Sdn Bhd',
    'registration_number' => '202301987654',
    'registration_date' => new \DateTime('2023-03-01'),
    'jurisdiction' => 'Malaysia',
    'status' => 'active',
    'parent_company_id' => $parentCompany->getId(),
    'financial_year_start_month' => 1,
    'industry' => 'Manufacturing',
    'size' => 'Medium',
    'tax_id' => 'C9876543210',
]);
```

### Step 2: Create Offices

```php
// Create head office
$headOffice = $manager->createOffice([
    'company_id' => $subsidiary->getId(),
    'code' => 'HQ',
    'name' => 'Headquarters',
    'type' => 'head_office',
    'status' => 'active',
    'address_line1' => 'Level 10, Menara ABC',
    'address_line2' => 'Jalan Ampang',
    'city' => 'Kuala Lumpur',
    'state' => 'Federal Territory',
    'postal_code' => '50450',
    'country' => 'Malaysia',
    'timezone' => 'Asia/Kuala_Lumpur',
    'phone' => '+60312345678',
    'email' => 'hq@abcmanufacturing.com',
    'staff_capacity' => 200,
    'metadata' => [
        'floor_area' => '10,000 sqft',
        'parking_spaces' => 50,
    ],
]);

// Create branch office
$branchOffice = $manager->createOffice([
    'company_id' => $subsidiary->getId(),
    'code' => 'JB01',
    'name' => 'Johor Bahru Branch',
    'type' => 'branch',
    'status' => 'active',
    'address_line1' => 'Lot 123, Jalan Setia',
    'city' => 'Johor Bahru',
    'state' => 'Johor',
    'postal_code' => '80100',
    'country' => 'Malaysia',
    'timezone' => 'Asia/Kuala_Lumpur',
    'phone' => '+60712345678',
    'email' => 'jb@abcmanufacturing.com',
    'staff_capacity' => 50,
]);
```

### Step 3: Create Department Hierarchy

```php
// Top-level departments
$finance = $manager->createDepartment([
    'company_id' => $subsidiary->getId(),
    'code' => 'FIN',
    'name' => 'Finance Department',
    'type' => 'functional',
    'status' => 'active',
    'cost_center' => 'CC-FIN-001',
]);

$operations = $manager->createDepartment([
    'company_id' => $subsidiary->getId(),
    'code' => 'OPS',
    'name' => 'Operations Department',
    'type' => 'functional',
    'status' => 'active',
    'cost_center' => 'CC-OPS-001',
]);

// Sub-departments under Finance
$accounting = $manager->createDepartment([
    'company_id' => $subsidiary->getId(),
    'parent_department_id' => $finance->getId(),
    'code' => 'ACC',
    'name' => 'Accounting',
    'type' => 'functional',
    'status' => 'active',
    'cost_center' => 'CC-FIN-ACC',
]);

$accountsPayable = $manager->createDepartment([
    'company_id' => $subsidiary->getId(),
    'parent_department_id' => $accounting->getId(),
    'code' => 'AP',
    'name' => 'Accounts Payable',
    'type' => 'functional',
    'status' => 'active',
    'cost_center' => 'CC-FIN-ACC-AP',
]);

$accountsReceivable = $manager->createDepartment([
    'company_id' => $subsidiary->getId(),
    'parent_department_id' => $accounting->getId(),
    'code' => 'AR',
    'name' => 'Accounts Receivable',
    'type' => 'functional',
    'status' => 'active',
    'cost_center' => 'CC-FIN-ACC-AR',
]);
```

### Step 4: Hire Staff

```php
// Hire CFO
$cfo = $manager->createStaff([
    'employee_id' => 'EMP001',
    'staff_code' => 'CFO001',
    'first_name' => 'Sarah',
    'last_name' => 'Chen',
    'email' => 'sarah.chen@abcmanufacturing.com',
    'phone' => '+60123456789',
    'type' => 'permanent',
    'status' => 'active',
    'hire_date' => new \DateTime('2023-04-01'),
    'position' => 'Chief Financial Officer',
    'grade' => 'Executive',
    'salary_band' => 'E1',
    'confirmation_date' => new \DateTime('2023-10-01'),
]);

// Assign CFO to Finance Department as head
$manager->assignStaffToDepartment(
    staffId: $cfo->getId(),
    departmentId: $finance->getId(),
    role: 'Department Head',
    isPrimary: true
);

// Hire Accounting Manager
$accountingManager = $manager->createStaff([
    'employee_id' => 'EMP002',
    'staff_code' => 'ACC001',
    'first_name' => 'Ahmad',
    'last_name' => 'Ibrahim',
    'email' => 'ahmad.ibrahim@abcmanufacturing.com',
    'phone' => '+60123456780',
    'type' => 'permanent',
    'status' => 'active',
    'hire_date' => new \DateTime('2023-05-01'),
    'position' => 'Accounting Manager',
    'grade' => 'Manager',
    'salary_band' => 'M3',
    'confirmation_date' => new \DateTime('2023-11-01'),
]);

// Assign to Accounting and set supervisor
$manager->assignStaffToDepartment(
    staffId: $accountingManager->getId(),
    departmentId: $accounting->getId(),
    role: 'Department Head',
    isPrimary: true
);

$manager->setSupervisor(
    staffId: $accountingManager->getId(),
    supervisorId: $cfo->getId()
);

// Hire AP Clerk
$apClerk = $manager->createStaff([
    'employee_id' => 'EMP003',
    'staff_code' => 'AP001',
    'first_name' => 'Nurul',
    'last_name' => 'Hassan',
    'email' => 'nurul.hassan@abcmanufacturing.com',
    'type' => 'permanent',
    'status' => 'active',
    'hire_date' => new \DateTime('2023-06-15'),
    'position' => 'Accounts Payable Clerk',
    'grade' => 'Executive',
    'salary_band' => 'E5',
]);

$manager->assignStaffToDepartment(
    staffId: $apClerk->getId(),
    departmentId: $accountsPayable->getId(),
    role: 'Team Member',
    isPrimary: true
);

$manager->setSupervisor(
    staffId: $apClerk->getId(),
    supervisorId: $accountingManager->getId()
);
```

### Step 5: Create Matrix Unit

```php
// Create cross-functional digital transformation committee
$digitalCommittee = $manager->createUnit([
    'company_id' => $subsidiary->getId(),
    'code' => 'DIGCOM',
    'name' => 'Digital Transformation Committee',
    'type' => 'committee',
    'status' => 'active',
    'description' => 'Cross-functional team driving digital initiatives',
    'start_date' => new \DateTime('2023-07-01'),
    'end_date' => new \DateTime('2024-12-31'),
]);

// Add members from different departments
$manager->addUnitMember(
    unitId: $digitalCommittee->getId(),
    staffId: $cfo->getId(),
    role: 'leader'
);

$manager->addUnitMember(
    unitId: $digitalCommittee->getId(),
    staffId: $accountingManager->getId(),
    role: 'member'
);
```

---

## Advanced Topics

### Nested Set Hierarchies

For efficient hierarchical queries, implement the nested set model in your database:

```php
// Get all descendants of a department (one query)
$descendants = app(DepartmentRepositoryInterface::class)
    ->getDescendants($finance->getId());

// Get parent chain (one query)
$ancestors = app(DepartmentRepositoryInterface::class)
    ->getAncestors($accountsPayable->getId());
```

**Database Schema for Nested Sets:**
```sql
-- Add to departments table
ALTER TABLE departments 
    ADD COLUMN lft INTEGER NOT NULL DEFAULT 0,
    ADD COLUMN rgt INTEGER NOT NULL DEFAULT 0,
    ADD COLUMN depth INTEGER NOT NULL DEFAULT 0;

CREATE INDEX idx_departments_lft ON departments(lft);
CREATE INDEX idx_departments_rgt ON departments(rgt);
```

### Transfer Approvals

Implement a complete transfer workflow:

```php
use Nexus\Backoffice\Services\TransferManager;

$transferManager = app(TransferManager::class);

// Step 1: Create transfer request
$transfer = $transferManager->createTransferRequest([
    'staff_id' => $apClerk->getId(),
    'from_department_id' => $accountsPayable->getId(),
    'to_department_id' => $accountsReceivable->getId(),
    'type' => 'lateral_move',
    'effective_date' => new \DateTime('+14 days'),
    'reason' => 'Resource balancing between AP and AR teams',
    'requested_by' => $accountingManager->getId(),
]);

// Step 2: Approve transfer
$transfer = $transferManager->approveTransfer(
    transferId: $transfer->getId(),
    approvedBy: $cfo->getId(),
    comment: 'Approved. Good opportunity for Nurul to gain AR experience.'
);

// Step 3: Complete transfer (execute on effective date)
$transfer = $transferManager->completeTransfer($transfer->getId());

// Optional: Rollback if needed
if ($needsRollback) {
    $transferManager->rollbackTransfer($transfer->getId());
}
```

### Organizational Charts

Generate visual representations:

```php
// Hierarchical tree format
$orgChart = $manager->generateOrganizationalChart(
    companyId: $subsidiary->getId(),
    format: 'tree',
    options: [
        'include_photos' => true,
        'show_positions' => true,
        'max_depth' => 5,
    ]
);

// Matrix view showing cross-functional units
$matrixChart = $manager->generateOrganizationalChart(
    companyId: $subsidiary->getId(),
    format: 'matrix',
    options: [
        'include_units' => true,
    ]
);

// Export to various formats
$svg = $manager->exportOrganizationalChart($orgChart, 'svg');
$pdf = $manager->exportOrganizationalChart($orgChart, 'pdf');
$json = $manager->exportOrganizationalChart($orgChart, 'json');
```

---

## Troubleshooting

### Issue: Circular reference error when updating parent company

**Solution:** Ensure the new parent is not a descendant of the current company:

```php
try {
    $manager->updateCompany($companyId, [
        'parent_company_id' => $newParentId,
    ]);
} catch (CircularReferenceException $e) {
    // Handle error: Cannot set a subsidiary as parent
}
```

### Issue: Cannot delete department with active staff

**Solution:** Reassign staff first, then delete:

```php
// Get all staff in department
$staff = $departmentRepository->getStaffInDepartment($departmentId);

// Reassign each staff member
foreach ($staff as $member) {
    $manager->assignStaffToDepartment(
        staffId: $member->getId(),
        departmentId: $newDepartmentId,
        role: $member->getRole(),
        isPrimary: true
    );
}

// Now safe to delete
$manager->deleteDepartment($departmentId);
```

### Issue: Transfer request blocked due to pending transfer

**Solution:** Cancel or complete the pending transfer first:

```php
// Get pending transfers for staff
$pending = $transferManager->getStaffTransferHistory($staffId);
$pendingTransfer = collect($pending)->firstWhere('status', 'pending');

if ($pendingTransfer) {
    // Cancel it
    $transferManager->cancelTransfer($pendingTransfer->getId());
    
    // Now create new transfer
    $newTransfer = $transferManager->createTransferRequest([...]);
}
```

### Issue: Supervisor chain exceeds 15 levels

**Solution:** Flatten the organizational structure:

```php
// Check supervisor chain depth before assignment
$chain = $staffRepository->getSupervisorChain($staffId);

if (count($chain) >= 15) {
    throw new InvalidHierarchyException(
        'Supervisor chain cannot exceed 15 levels'
    );
}
```

### Issue: Performance degradation with deep hierarchies

**Solution:** Implement nested set model and add database indexes:

```sql
-- Index for nested set queries
CREATE INDEX idx_departments_nested ON departments(lft, rgt);

-- Index for parent lookups
CREATE INDEX idx_departments_parent ON departments(parent_department_id);

-- Index for company filtering
CREATE INDEX idx_departments_company ON departments(company_id);
```

---

## Next Steps

- **Read the [API Reference](api-reference.md)** for complete method documentation
- **Review the [Integration Guide](integration-guide.md)** for Laravel/Symfony examples
- **Explore example implementations** in the `docs/examples/` folder

## Support

For questions or issues:
- Open an issue on GitHub
- Refer to package documentation
- Review the REQUIREMENTS.md file for detailed specifications
