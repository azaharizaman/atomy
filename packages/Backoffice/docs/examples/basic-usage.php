<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Backoffice
 * 
 * This example demonstrates:
 * 1. Creating a company with hierarchical structure
 * 2. Setting up offices and departments
 * 3. Hiring staff and assigning to departments
 * 4. Basic organizational queries
 */

use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\ValueObjects\{CompanyStatus, OfficeType, DepartmentType, StaffType, StaffStatus};

// ============================================
// SCENARIO: New Company Setup
// ============================================

/**
 * This example simulates setting up a new company structure:
 * - Parent company (holding company)
 * - Subsidiary company
 * - Head office and branch
 * - Departments (Sales, Engineering)
 * - Hiring staff
 */

// Assume we have the service injected (via DI container)
/** @var BackofficeManagerInterface $backoffice */

$tenantId = 'tenant-abc123';

// ============================================
// Step 1: Create Parent Company (Holding Company)
// ============================================

echo "=== Step 1: Creating Parent Company ===\n";

$parentCompanyId = $backoffice->createCompany(
    tenantId: $tenantId,
    code: 'ACME',
    name: 'ACME Holdings Ltd',
    registrationNumber: 'REG-2020-001',
    registrationDate: new \DateTimeImmutable('2020-01-01'),
    fiscalYearEnd: '12-31',
    metadata: [
        'industry' => 'Technology',
        'jurisdiction' => 'Malaysia',
    ]
);

echo "✓ Parent company created: {$parentCompanyId}\n";
echo "  Code: ACME\n";
echo "  Name: ACME Holdings Ltd\n";
echo "  Status: Active (default)\n\n";

// ============================================
// Step 2: Create Subsidiary Company
// ============================================

echo "=== Step 2: Creating Subsidiary Company ===\n";

$subsidiaryId = $backoffice->createCompany(
    tenantId: $tenantId,
    code: 'ACME-TECH',
    name: 'ACME Technologies Sdn Bhd',
    registrationNumber: 'REG-2021-045',
    registrationDate: new \DateTimeImmutable('2021-03-15'),
    fiscalYearEnd: '12-31',
    parentId: $parentCompanyId, // ← Hierarchical relationship
    metadata: [
        'industry' => 'Software Development',
        'jurisdiction' => 'Malaysia',
    ]
);

echo "✓ Subsidiary company created: {$subsidiaryId}\n";
echo "  Code: ACME-TECH\n";
echo "  Parent: ACME Holdings Ltd\n";
echo "  Hierarchy: Parent → Subsidiary\n\n";

// ============================================
// Step 3: Create Head Office
// ============================================

echo "=== Step 3: Creating Head Office ===\n";

$headOfficeId = $backoffice->createOffice(
    tenantId: $tenantId,
    companyId: $subsidiaryId,
    code: 'HQ-KL',
    name: 'Kuala Lumpur Head Office',
    type: OfficeType::HeadOffice,
    address: 'Menara ACME, Jalan Sultan Ismail',
    city: 'Kuala Lumpur',
    state: 'Wilayah Persekutuan',
    postalCode: '50250',
    country: 'Malaysia',
    metadata: [
        'phone' => '+60-3-2161-0000',
        'email' => 'hq@acmetech.com',
        'capacity' => 200,
    ]
);

echo "✓ Head office created: {$headOfficeId}\n";
echo "  Code: HQ-KL\n";
echo "  Type: Head Office\n";
echo "  Location: Kuala Lumpur, Malaysia\n\n";

// ============================================
// Step 4: Create Departments
// ============================================

echo "=== Step 4: Creating Departments ===\n";

// Create Sales Department
$salesDeptId = $backoffice->createDepartment(
    tenantId: $tenantId,
    companyId: $subsidiaryId,
    code: 'SALES',
    name: 'Sales Department',
    type: DepartmentType::Functional,
    metadata: [
        'cost_center' => 'CC-001',
        'budget_code' => 'SALES-2024',
    ]
);

echo "✓ Sales department created: {$salesDeptId}\n";
echo "  Code: SALES\n";
echo "  Type: Functional\n\n";

// Create Engineering Department
$engineeringDeptId = $backoffice->createDepartment(
    tenantId: $tenantId,
    companyId: $subsidiaryId,
    code: 'ENG',
    name: 'Engineering Department',
    type: DepartmentType::Functional,
    metadata: [
        'cost_center' => 'CC-002',
        'budget_code' => 'ENG-2024',
    ]
);

echo "✓ Engineering department created: {$engineeringDeptId}\n";
echo "  Code: ENG\n";
echo "  Type: Functional\n\n";

// ============================================
// Step 5: Hire Staff
// ============================================

echo "=== Step 5: Hiring Staff ===\n";

// Hire Sales Manager
$salesManagerId = $backoffice->hireStaff(
    tenantId: $tenantId,
    employeeNumber: 'EMP-001',
    firstName: 'John',
    lastName: 'Doe',
    email: 'john.doe@acmetech.com',
    type: StaffType::Permanent,
    hireDate: new \DateTimeImmutable('2024-01-15'),
    jobTitle: 'Sales Manager',
    departmentId: $salesDeptId,
    metadata: [
        'phone' => '+60-12-345-6789',
        'date_of_birth' => '1985-05-20',
    ]
);

echo "✓ Staff hired: {$salesManagerId}\n";
echo "  Employee Number: EMP-001\n";
echo "  Name: John Doe\n";
echo "  Job Title: Sales Manager\n";
echo "  Department: Sales\n\n";

// Hire Software Engineer
$engineerId = $backoffice->hireStaff(
    tenantId: $tenantId,
    employeeNumber: 'EMP-002',
    firstName: 'Jane',
    lastName: 'Smith',
    email: 'jane.smith@acmetech.com',
    type: StaffType::Permanent,
    hireDate: new \DateTimeImmutable('2024-02-01'),
    jobTitle: 'Senior Software Engineer',
    departmentId: $engineeringDeptId,
    metadata: [
        'phone' => '+60-12-987-6543',
        'date_of_birth' => '1990-08-15',
    ]
);

echo "✓ Staff hired: {$engineerId}\n";
echo "  Employee Number: EMP-002\n";
echo "  Name: Jane Smith\n";
echo "  Job Title: Senior Software Engineer\n";
echo "  Department: Engineering\n\n";

// ============================================
// Step 6: Query Organizational Structure
// ============================================

echo "=== Step 6: Querying Organizational Structure ===\n";

// Get company hierarchy
$subsidiaryCompany = $backoffice->getCompany($subsidiaryId);
echo "Company: {$subsidiaryCompany->getName()}\n";
echo "  Parent: {$subsidiaryCompany->getParent()?->getName() ?? 'None'}\n";
echo "  Status: {$subsidiaryCompany->getStatus()->name}\n";
echo "\n";

// Get all offices for company
$offices = $backoffice->getOfficesByCompany($subsidiaryId);
echo "Offices (" . count($offices) . "):\n";
foreach ($offices as $office) {
    echo "  - {$office->getName()} ({$office->getType()->value})\n";
}
echo "\n";

// Get all departments for company
$departments = $backoffice->getDepartmentsByCompany($subsidiaryId);
echo "Departments (" . count($departments) . "):\n";
foreach ($departments as $dept) {
    echo "  - {$dept->getName()} ({$dept->getType()->value})\n";
}
echo "\n";

// Get headcount by department
$salesHeadcount = $backoffice->getHeadcountByDepartment($salesDeptId);
$engHeadcount = $backoffice->getHeadcountByDepartment($engineeringDeptId);

echo "Headcount Report:\n";
echo "  Sales Department: {$salesHeadcount} staff\n";
echo "  Engineering Department: {$engHeadcount} staff\n";
echo "  Total: " . ($salesHeadcount + $engHeadcount) . " staff\n";
echo "\n";

// ============================================
// Step 7: Get Active Staff List
// ============================================

echo "=== Step 7: Active Staff List ===\n";

$activeStaff = $backoffice->getStaffByStatus(StaffStatus::Active);

echo "Active Staff (" . count($activeStaff) . "):\n";
foreach ($activeStaff as $staff) {
    echo "  {$staff->getEmployeeNumber()} - {$staff->getFirstName()} {$staff->getLastName()}\n";
    echo "    Job Title: {$staff->getJobTitle()}\n";
    echo "    Email: {$staff->getEmail()}\n";
    echo "    Status: {$staff->getStatus()->name}\n";
    echo "\n";
}

// ============================================
// Summary
// ============================================

echo "=== Summary ===\n";
echo "Organizational structure created successfully:\n";
echo "  ✓ 2 companies (parent + subsidiary)\n";
echo "  ✓ 1 head office\n";
echo "  ✓ 2 departments (Sales, Engineering)\n";
echo "  ✓ 2 staff members hired\n";
echo "\n";
echo "Next steps:\n";
echo "  - Add more departments (HR, Finance, Operations)\n";
echo "  - Create branch offices\n";
echo "  - Set up department hierarchies (sub-departments)\n";
echo "  - Create cross-functional units/teams\n";
echo "  - Initiate staff transfers\n";
echo "\n";

/**
 * Expected Output:
 * 
 * === Step 1: Creating Parent Company ===
 * ✓ Parent company created: 01HX...
 * Code: ACME
 * Name: ACME Holdings Ltd
 * Status: Active (default)
 * 
 * === Step 2: Creating Subsidiary Company ===
 * ✓ Subsidiary company created: 01HX...
 * Code: ACME-TECH
 * Parent: ACME Holdings Ltd
 * Hierarchy: Parent → Subsidiary
 * 
 * [... additional steps ...]
 * 
 * === Summary ===
 * Organizational structure created successfully:
 * ✓ 2 companies (parent + subsidiary)
 * ✓ 1 head office
 * ✓ 2 departments (Sales, Engineering)
 * ✓ 2 staff members hired
 */
