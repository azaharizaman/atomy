# Nexus\Backoffice

Framework-agnostic company structure, offices, departments, and staff organizational units management package for Nexus ERP.

## Overview

The Backoffice package provides a comprehensive organizational structure management system for companies, including:

- **Company Hierarchy**: Multi-level parent-child company relationships for holding structures
- **Office Management**: Physical location management with types (head office, branch, regional, satellite, virtual)
- **Department Structure**: Hierarchical departmental organization independent of physical locations
- **Staff Management**: Comprehensive employee data and assignment tracking
- **Unit/Matrix Organization**: Cross-functional teams that transcend traditional hierarchy boundaries
- **Transfer Management**: Staff transfer workflows with approval and effective date scheduling
- **Organizational Charts**: Generation and visualization of organizational structures
- **Reporting**: Headcount, turnover, span of control, and vacancy reports

## Architecture

This package follows the **Nexus Architecture Principle**: "Logic in Packages, Implementation in Applications."

### Package Layer (Pure PHP)
- **Framework-agnostic**: No Laravel dependencies
- **Business Logic**: All organizational management rules and workflows
- **Interfaces**: Defines data structures and persistence contracts
- **Value Objects**: Immutable domain objects (CompanyStatus, OfficeType, StaffType, etc.)
- **Services**: BackofficeManager, TransferManager for orchestration

### Application Layer (Laravel/Atomy)
- **Eloquent Models**: Company, Office, Department, Staff, Unit, Transfer
- **Repository Implementations**: Concrete persistence implementations
- **Database Migrations**: Schema definitions
- **Service Provider**: IoC container bindings

## Installation

```bash
composer require nexus/backoffice:"*@dev"
```

## Key Features

### Company Management
- Multi-level company hierarchies (holding companies, subsidiaries)
- Company registration details (number, date, jurisdiction)
- Status tracking (active, inactive, suspended, dissolved)
- Financial year and metadata management

### Office Management
- Office types: head office, branch, regional, satellite, virtual
- Location data: address, country, postal code, timezone
- Contact details: phone, email, fax
- Operating hours and capacity tracking

### Department Management
- Hierarchical department structure up to 8 levels deep
- Department types: functional, divisional, matrix, project-based
- Cost center and budget allocation tracking
- Department head (manager) assignment

### Staff Management
- Comprehensive staff profiles with personal and employment details
- Staff types: permanent, contract, temporary, intern, consultant
- Status tracking: active, inactive, on leave, terminated
- Multiple department assignments with different roles
- Supervisor-subordinate relationships
- Skills, competencies, qualifications tracking

### Unit/Matrix Organization
- Cross-functional teams spanning departments and offices
- Unit types: project team, committee, task force, working group, CoE
- Member roles: leader, member, secretary, advisor
- Temporary units with start and end dates

### Transfer Management
- Transfer types: promotion, lateral move, demotion, relocation
- Multi-level approval workflows
- Effective date scheduling (future or within 30 days past)
- Transfer history and rollback support

### Reporting & Analytics
- Organizational charts (hierarchical tree, matrix view, circle pack)
- Headcount reports by company, office, department, type, status
- Turnover and retention metrics
- Span of control reports
- Vacancy reports

## Usage

### Basic Company Setup

```php
use Nexus\Backoffice\Services\BackofficeManager;

$manager = app(BackofficeManager::class);

// Create a company
$company = $manager->createCompany([
    'code' => 'ABC',
    'name' => 'ABC Corporation',
    'registration_number' => '123456789',
    'registration_date' => '2020-01-01',
    'status' => 'active',
]);

// Add head office
$office = $manager->createOffice([
    'company_id' => $company->getId(),
    'code' => 'HQ',
    'name' => 'Head Office',
    'type' => 'head_office',
    'address_line1' => '123 Main Street',
    'city' => 'Kuala Lumpur',
    'country' => 'Malaysia',
    'postal_code' => '50000',
]);

// Create department
$department = $manager->createDepartment([
    'company_id' => $company->getId(),
    'code' => 'IT',
    'name' => 'Information Technology',
    'type' => 'functional',
]);

// Onboard staff
$staff = $manager->createStaff([
    'employee_id' => 'EMP001',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@abc.com',
    'hire_date' => '2024-01-01',
    'type' => 'permanent',
    'status' => 'active',
]);

// Assign staff to department
$manager->assignStaffToDepartment(
    $staff->getId(),
    $department->getId(),
    'Software Engineer',
    true // is_primary
);
```

### Transfer Management

```php
use Nexus\Backoffice\Services\TransferManager;

$transferManager = app(TransferManager::class);

// Create transfer request
$transfer = $transferManager->createTransferRequest([
    'staff_id' => $staff->getId(),
    'from_department_id' => $currentDepartment->getId(),
    'to_department_id' => $newDepartment->getId(),
    'transfer_type' => 'promotion',
    'effective_date' => '2024-06-01',
    'reason' => 'Promotion to Senior Developer',
]);

// Approve transfer
$transferManager->approveTransfer(
    $transfer->getId(),
    $approverId,
    'Approved for promotion'
);
```

### Organizational Chart

```php
// Generate organizational chart
$orgChart = $manager->generateOrganizationalChart(
    $company->getId(),
    'hierarchical_tree'
);

// Export to JSON
$json = $manager->exportOrganizationalChart(
    $orgChart,
    'json'
);
```

## Business Rules

### Company Rules
- Company codes must be unique across the system
- Company registration number must be unique when provided
- Parent company relationship cannot create circular references
- Inactive companies cannot have new staff assignments
- Parent company must be active to have active children

### Office Rules
- Office codes must be unique within the same company
- Office hierarchy cannot exceed company boundaries
- Only one head office per company
- Office cannot be deleted if it has active staff assignments
- Office address must include country and postal code

### Department Rules
- Department codes must be unique within the same parent department
- Department hierarchy is independent of office structure
- Department cannot be deleted if it has active staff or sub-departments
- Department hierarchy depth recommended maximum is 8 levels
- Cost center code must be unique within company when provided

### Staff Rules
- Staff employee ID must be unique across entire system
- Staff codes must be unique system-wide
- Staff email address must be unique within company when provided
- Staff can only have one primary supervisor per company
- Supervisor must be in same or parent organizational unit
- Staff cannot be their own supervisor (direct or indirect)
- Supervisor chain cannot exceed 15 levels
- Terminated staff cannot have active assignments

### Transfer Rules
- Transfer requires approval from authorized users
- Transfer effective dates cannot be retroactive beyond 30 days
- Pending transfer blocks new transfers for same staff
- Transfer approval requires authorized approver at source and destination

## Multi-Tenancy

The package supports multi-tenancy via tenant context injection. All operations are automatically scoped to the current tenant when integrated with the `Nexus\Tenant` package.

## Performance

- Organizational chart generation: < 2 seconds for 10,000 staff
- Staff search and filtering: < 500ms
- Hierarchical queries (ancestors/descendants): < 100ms
- Support up to 100,000 staff records per company

## Security

- Tenant isolation enforced at repository level
- Role-based access control for organizational management operations
- Field-level security for sensitive staff information
- Audit trail for all organizational changes
- Data encryption at rest and in transit

## Requirements

- PHP 8.3 or higher
- No framework dependencies (framework-agnostic)

## License

MIT License. See LICENSE file for details.

## Contributing

This package is part of the Nexus monorepo. Please refer to the main repository for contribution guidelines.
