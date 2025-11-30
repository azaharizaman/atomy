# API Reference: Backoffice

## Table of Contents

- [Interfaces](#interfaces)
  - [Entity Interfaces](#entity-interfaces)
  - [Repository Interfaces](#repository-interfaces)
  - [Service Interfaces](#service-interfaces)
- [Services](#services)
- [Value Objects & Enums](#value-objects--enums)
- [Exceptions](#exceptions)
- [Usage Patterns](#usage-patterns)

---

## Interfaces

### Entity Interfaces

#### CompanyInterface

**Location:** `src/Contracts/CompanyInterface.php`

**Purpose:** Defines the structure and operations for a Company entity

**Methods:**

##### getId()

```php
public function getId(): string;
```

**Description:** Get the unique identifier for the company (ULID)

**Returns:** `string` - The company ID

---

##### getCode()

```php
public function getCode(): string;
```

**Description:** Get the unique company code

**Returns:** `string` - The company code (e.g., "ABC", "HOLDINGS")

---

##### getName()

```php
public function getName(): string;
```

**Description:** Get the company name

**Returns:** `string` - The full company name

---

##### getRegistrationNumber()

```php
public function getRegistrationNumber(): ?string;
```

**Description:** Get the company registration number

**Returns:** `string|null` - The registration number or null

---

##### getRegistrationDate()

```php
public function getRegistrationDate(): ?\DateTimeInterface;
```

**Description:** Get the company registration date

**Returns:** `\DateTimeInterface|null` - The registration date or null

---

##### getJurisdiction()

```php
public function getJurisdiction(): ?string;
```

**Description:** Get the registration jurisdiction (country/state)

**Returns:** `string|null` - The jurisdiction or null

---

##### getStatus()

```php
public function getStatus(): string;
```

**Description:** Get the company status

**Returns:** `string` - Status value from CompanyStatus enum

---

##### getParentCompanyId()

```php
public function getParentCompanyId(): ?string;
```

**Description:** Get the parent company ID for holding structures

**Returns:** `string|null` - The parent company ID or null for top-level companies

---

##### getFinancialYearStartMonth()

```php
public function getFinancialYearStartMonth(): ?int;
```

**Description:** Get the financial year start month (1-12)

**Returns:** `int|null` - The month number (1=January) or null

---

##### getIndustry()

```php
public function getIndustry(): ?string;
```

**Description:** Get the company industry classification

**Returns:** `string|null` - The industry or null

---

##### getSize()

```php
public function getSize(): ?string;
```

**Description:** Get the company size category (Small, Medium, Large)

**Returns:** `string|null` - The size category or null

---

##### getTaxId()

```php
public function getTaxId(): ?string;
```

**Description:** Get the tax identification number

**Returns:** `string|null` - The tax ID or null

---

##### getMetadata()

```php
public function getMetadata(): array;
```

**Description:** Get additional metadata

**Returns:** `array<string, mixed>` - Metadata array

---

##### getCreatedAt()

```php
public function getCreatedAt(): \DateTimeInterface;
```

**Description:** Get the creation timestamp

**Returns:** `\DateTimeInterface` - Creation timestamp

---

##### getUpdatedAt()

```php
public function getUpdatedAt(): \DateTimeInterface;
```

**Description:** Get the last update timestamp

**Returns:** `\DateTimeInterface` - Update timestamp

---

##### isActive()

```php
public function isActive(): bool;
```

**Description:** Check if the company is active

**Returns:** `bool` - True if active, false otherwise

---

#### OfficeInterface

**Location:** `src/Contracts/OfficeInterface.php`

**Purpose:** Defines the structure and operations for an Office entity

**Methods:**

##### getId()
```php
public function getId(): string;
```
Get the unique identifier (ULID)

##### getCompanyId()
```php
public function getCompanyId(): string;
```
Get the associated company ID

##### getCode()
```php
public function getCode(): string;
```
Get the unique office code within the company

##### getName()
```php
public function getName(): string;
```
Get the office name

##### getType()
```php
public function getType(): string;
```
Get the office type (from OfficeType enum)

##### getStatus()
```php
public function getStatus(): string;
```
Get the office status (from OfficeStatus enum)

##### getAddressLine1()
```php
public function getAddressLine1(): ?string;
```
Get address line 1

##### getAddressLine2()
```php
public function getAddressLine2(): ?string;
```
Get address line 2

##### getCity()
```php
public function getCity(): ?string;
```
Get city

##### getState()
```php
public function getState(): ?string;
```
Get state/province

##### getPostalCode()
```php
public function getPostalCode(): ?string;
```
Get postal/ZIP code

##### getCountry()
```php
public function getCountry(): ?string;
```
Get country (ISO 3166-1 alpha-2 code)

##### getTimezone()
```php
public function getTimezone(): ?string;
```
Get timezone (e.g., "Asia/Kuala_Lumpur")

##### getPhone()
```php
public function getPhone(): ?string;
```
Get primary phone number

##### getEmail()
```php
public function getEmail(): ?string;
```
Get office email address

##### getFax()
```php
public function getFax(): ?string;
```
Get fax number

##### getStaffCapacity()
```php
public function getStaffCapacity(): ?int;
```
Get maximum staff capacity

##### getMetadata()
```php
public function getMetadata(): array;
```
Get additional metadata

##### getCreatedAt()
```php
public function getCreatedAt(): \DateTimeInterface;
```
Get creation timestamp

##### getUpdatedAt()
```php
public function getUpdatedAt(): \DateTimeInterface;
```
Get last update timestamp

##### isActive()
```php
public function isActive(): bool;
```
Check if office is active

---

#### DepartmentInterface

**Location:** `src/Contracts/DepartmentInterface.php`

**Purpose:** Defines the structure and operations for a Department entity

**Key Methods:**

##### getId()
```php
public function getId(): string;
```
Get the unique identifier (ULID)

##### getCompanyId()
```php
public function getCompanyId(): string;
```
Get the associated company ID

##### getParentDepartmentId()
```php
public function getParentDepartmentId(): ?string;
```
Get parent department ID for hierarchical structure

##### getCode()
```php
public function getCode(): string;
```
Get department code (unique within parent)

##### getName()
```php
public function getName(): string;
```
Get department name

##### getType()
```php
public function getType(): string;
```
Get department type (from DepartmentType enum)

##### getStatus()
```php
public function getStatus(): string;
```
Get department status (from DepartmentStatus enum)

##### getHeadStaffId()
```php
public function getHeadStaffId(): ?string;
```
Get department head (manager) staff ID

##### getCostCenter()
```php
public function getCostCenter(): ?string;
```
Get cost center code for financial tracking

##### getBudgetAmount()
```php
public function getBudgetAmount(): ?float;
```
Get allocated budget amount

##### getDescription()
```php
public function getDescription(): ?string;
```
Get department description

##### getMetadata()
```php
public function getMetadata(): array;
```
Get additional metadata

##### getCreatedAt()
```php
public function getCreatedAt(): \DateTimeInterface;
```
Get creation timestamp

##### getUpdatedAt()
```php
public function getUpdatedAt(): \DateTimeInterface;
```
Get last update timestamp

##### isActive()
```php
public function isActive(): bool;
```
Check if department is active

---

#### StaffInterface

**Location:** `src/Contracts/StaffInterface.php`

**Purpose:** Defines the structure and operations for a Staff entity

**Key Methods:**

##### getId()
```php
public function getId(): string;
```
Get the unique identifier (ULID)

##### getEmployeeId()
```php
public function getEmployeeId(): string;
```
Get the unique employee ID (system-wide)

##### getStaffCode()
```php
public function getStaffCode(): ?string;
```
Get the staff code (optional identifier)

##### getFirstName()
```php
public function getFirstName(): string;
```
Get first name

##### getLastName()
```php
public function getLastName(): string;
```
Get last name

##### getMiddleName()
```php
public function getMiddleName(): ?string;
```
Get middle name

##### getFullName()
```php
public function getFullName(): string;
```
Get computed full name

##### getEmail()
```php
public function getEmail(): ?string;
```
Get email address (unique within company)

##### getPhone()
```php
public function getPhone(): ?string;
```
Get phone number

##### getMobile()
```php
public function getMobile(): ?string;
```
Get mobile number

##### getEmergencyContact()
```php
public function getEmergencyContact(): ?string;
```
Get emergency contact name

##### getEmergencyPhone()
```php
public function getEmergencyPhone(): ?string;
```
Get emergency contact phone

##### getType()
```php
public function getType(): string;
```
Get staff type (from StaffType enum)

##### getStatus()
```php
public function getStatus(): string;
```
Get staff status (from StaffStatus enum)

##### getHireDate()
```php
public function getHireDate(): \DateTimeInterface;
```
Get hire/start date

##### getTerminationDate()
```php
public function getTerminationDate(): ?\DateTimeInterface;
```
Get termination date (if applicable)

##### getPosition()
```php
public function getPosition(): ?string;
```
Get job position/title

##### getGrade()
```php
public function getGrade(): ?string;
```
Get job grade

##### getSalaryBand()
```php
public function getSalaryBand(): ?string;
```
Get salary band

##### getProbationEndDate()
```php
public function getProbationEndDate(): ?\DateTimeInterface;
```
Get probation end date

##### getConfirmationDate()
```php
public function getConfirmationDate(): ?\DateTimeInterface;
```
Get employment confirmation date

##### getPhotoUrl()
```php
public function getPhotoUrl(): ?string;
```
Get staff photo URL

##### getMetadata()
```php
public function getMetadata(): array;
```
Get additional metadata (skills, qualifications, etc.)

##### isActive()
```php
public function isActive(): bool;
```
Check if staff is active

##### isTerminated()
```php
public function isTerminated(): bool;
```
Check if staff is terminated

---

#### UnitInterface

**Location:** `src/Contracts/UnitInterface.php`

**Purpose:** Defines the structure and operations for a Unit entity (matrix organizations)

**Key Methods:**

##### getId()
```php
public function getId(): string;
```
Get the unique identifier (ULID)

##### getCompanyId()
```php
public function getCompanyId(): string;
```
Get the associated company ID

##### getCode()
```php
public function getCode(): string;
```
Get unit code (unique within company)

##### getName()
```php
public function getName(): string;
```
Get unit name

##### getType()
```php
public function getType(): string;
```
Get unit type (from UnitType enum)

##### getStatus()
```php
public function getStatus(): string;
```
Get unit status (from UnitStatus enum)

##### getDescription()
```php
public function getDescription(): ?string;
```
Get unit description

##### getStartDate()
```php
public function getStartDate(): ?\DateTimeInterface;
```
Get unit start date

##### getEndDate()
```php
public function getEndDate(): ?\DateTimeInterface;
```
Get unit end date (for temporary units)

##### getLeaderStaffId()
```php
public function getLeaderStaffId(): ?string;
```
Get unit leader staff ID

##### getMetadata()
```php
public function getMetadata(): array;
```
Get additional metadata

##### isActive()
```php
public function isActive(): bool;
```
Check if unit is active

---

#### TransferInterface

**Location:** `src/Contracts/TransferInterface.php`

**Purpose:** Defines the structure and operations for a Transfer entity

**Key Methods:**

##### getId()
```php
public function getId(): string;
```
Get the unique identifier (ULID)

##### getStaffId()
```php
public function getStaffId(): string;
```
Get the staff being transferred

##### getFromDepartmentId()
```php
public function getFromDepartmentId(): string;
```
Get source department ID

##### getToDepartmentId()
```php
public function getToDepartmentId(): string;
```
Get destination department ID

##### getFromOfficeId()
```php
public function getFromOfficeId(): ?string;
```
Get source office ID (if applicable)

##### getToOfficeId()
```php
public function getToOfficeId(): ?string;
```
Get destination office ID (if applicable)

##### getType()
```php
public function getType(): string;
```
Get transfer type (from TransferType enum)

##### getStatus()
```php
public function getStatus(): string;
```
Get transfer status (from TransferStatus enum)

##### getEffectiveDate()
```php
public function getEffectiveDate(): \DateTimeInterface;
```
Get effective date of transfer

##### getReason()
```php
public function getReason(): ?string;
```
Get transfer reason

##### getRequestedBy()
```php
public function getRequestedBy(): string;
```
Get staff ID of requester

##### getApprovedBy()
```php
public function getApprovedBy(): ?string;
```
Get staff ID of approver

##### getApprovedAt()
```php
public function getApprovedAt(): ?\DateTimeInterface;
```
Get approval timestamp

##### getRejectedBy()
```php
public function getRejectedBy(): ?string;
```
Get staff ID of rejector

##### getRejectedAt()
```php
public function getRejectedAt(): ?\DateTimeInterface;
```
Get rejection timestamp

##### getCompletedAt()
```php
public function getCompletedAt(): ?\DateTimeInterface;
```
Get completion timestamp

---

### Repository Interfaces

#### CompanyRepositoryInterface

**Location:** `src/Contracts/CompanyRepositoryInterface.php`

**Purpose:** Defines persistence operations for Company entities

**Methods:**

##### save()
```php
public function save(array $data): CompanyInterface;
```
Create a new company

**Parameters:**
- `$data` - Company data array

**Returns:** `CompanyInterface` - The created company

---

##### update()
```php
public function update(string $id, array $data): CompanyInterface;
```
Update an existing company

**Parameters:**
- `$id` - Company ID
- `$data` - Updated company data

**Returns:** `CompanyInterface` - The updated company

---

##### delete()
```php
public function delete(string $id): bool;
```
Delete a company

**Parameters:**
- `$id` - Company ID

**Returns:** `bool` - True if deleted successfully

---

##### findById()
```php
public function findById(string $id): ?CompanyInterface;
```
Find company by ID

**Parameters:**
- `$id` - Company ID

**Returns:** `CompanyInterface|null` - The company or null

---

##### findByCode()
```php
public function findByCode(string $code): ?CompanyInterface;
```
Find company by code

**Parameters:**
- `$code` - Company code

**Returns:** `CompanyInterface|null` - The company or null

---

##### codeExists()
```php
public function codeExists(string $code, ?string $excludeId = null): bool;
```
Check if code exists

**Parameters:**
- `$code` - Company code to check
- `$excludeId` - Optional company ID to exclude (for updates)

**Returns:** `bool` - True if code exists

---

##### registrationNumberExists()
```php
public function registrationNumberExists(string $number, ?string $excludeId = null): bool;
```
Check if registration number exists

**Parameters:**
- `$number` - Registration number to check
- `$excludeId` - Optional company ID to exclude

**Returns:** `bool` - True if registration number exists

---

##### hasCircularReference()
```php
public function hasCircularReference(string $companyId, string $parentId): bool;
```
Check for circular reference in parent-child relationships

**Parameters:**
- `$companyId` - Company ID
- `$parentId` - Proposed parent company ID

**Returns:** `bool` - True if circular reference exists

---

##### getSubsidiaries()
```php
public function getSubsidiaries(string $companyId): array;
```
Get all subsidiary companies

**Parameters:**
- `$companyId` - Parent company ID

**Returns:** `array<CompanyInterface>` - Array of subsidiaries

---

##### getParentChain()
```php
public function getParentChain(string $companyId): array;
```
Get parent company chain

**Parameters:**
- `$companyId` - Company ID

**Returns:** `array<CompanyInterface>` - Array of parent companies (bottom to top)

---

##### findAll()
```php
public function findAll(): array;
```
Get all companies

**Returns:** `array<CompanyInterface>` - Array of all companies

---

#### OfficeRepositoryInterface

**Location:** `src/Contracts/OfficeRepositoryInterface.php`

**Purpose:** Defines persistence operations for Office entities

**Key Methods:**

- `save(array $data): OfficeInterface` - Create office
- `update(string $id, array $data): OfficeInterface` - Update office
- `delete(string $id): bool` - Delete office
- `findById(string $id): ?OfficeInterface` - Find by ID
- `findByCode(string $companyId, string $code): ?OfficeInterface` - Find by code within company
- `codeExists(string $companyId, string $code, ?string $excludeId = null): bool` - Check code uniqueness
- `findByCompany(string $companyId): array` - Get all offices for a company
- `findByType(string $companyId, string $type): array` - Get offices by type
- `hasActiveStaff(string $officeId): bool` - Check if office has active staff
- `findAll(): array` - Get all offices

---

#### DepartmentRepositoryInterface

**Location:** `src/Contracts/DepartmentRepositoryInterface.php`

**Purpose:** Defines persistence operations for Department entities with hierarchical support

**Key Methods:**

- `save(array $data): DepartmentInterface` - Create department
- `update(string $id, array $data): DepartmentInterface` - Update department
- `delete(string $id): bool` - Delete department
- `findById(string $id): ?DepartmentInterface` - Find by ID
- `findByCode(string $companyId, string $code, ?string $parentId = null): ?DepartmentInterface` - Find by code
- `codeExists(string $companyId, string $code, ?string $parentId = null, ?string $excludeId = null): bool` - Check code uniqueness within parent
- `getChildren(string $departmentId): array` - Get immediate children
- `getDescendants(string $departmentId): array` - Get all descendants (nested set query)
- `getAncestors(string $departmentId): array` - Get parent chain (nested set query)
- `move(string $departmentId, ?string $newParentId): bool` - Move department in hierarchy
- `hasActiveStaff(string $departmentId): bool` - Check if department has active staff
- `hasSubDepartments(string $departmentId): bool` - Check if department has children
- `findByCompany(string $companyId): array` - Get all departments for company
- `findAll(): array` - Get all departments

---

#### StaffRepositoryInterface

**Location:** `src/Contracts/StaffRepositoryInterface.php`

**Purpose:** Defines persistence operations for Staff entities

**Key Methods:**

- `save(array $data): StaffInterface` - Create staff
- `update(string $id, array $data): StaffInterface` - Update staff
- `delete(string $id): bool` - Delete staff
- `findById(string $id): ?StaffInterface` - Find by ID
- `findByEmployeeId(string $employeeId): ?StaffInterface` - Find by employee ID
- `employeeIdExists(string $employeeId, ?string $excludeId = null): bool` - Check employee ID uniqueness
- `emailExists(string $companyId, string $email, ?string $excludeId = null): bool` - Check email uniqueness within company
- `assignToDepartment(string $staffId, string $departmentId, string $role, bool $isPrimary): void` - Assign to department
- `assignToOffice(string $staffId, string $officeId, \DateTimeInterface $effectiveDate): void` - Assign to office
- `setSupervisor(string $staffId, string $supervisorId): void` - Set supervisor
- `getSupervisor(string $staffId): ?StaffInterface` - Get direct supervisor
- `getSupervisorChain(string $staffId): array` - Get supervisory chain
- `getSubordinates(string $staffId): array` - Get direct subordinates
- `getDepartmentAssignments(string $staffId): array` - Get department assignments
- `findByDepartment(string $departmentId): array` - Get staff in department
- `findByOffice(string $officeId): array` - Get staff in office
- `findByStatus(string $status): array` - Get staff by status
- `findAll(): array` - Get all staff

---

#### UnitRepositoryInterface

**Location:** `src/Contracts/UnitRepositoryInterface.php`

**Purpose:** Defines persistence operations for Unit entities

**Key Methods:**

- `save(array $data): UnitInterface` - Create unit
- `update(string $id, array $data): UnitInterface` - Update unit
- `delete(string $id): bool` - Delete unit
- `findById(string $id): ?UnitInterface` - Find by ID
- `findByCode(string $companyId, string $code): ?UnitInterface` - Find by code
- `codeExists(string $companyId, string $code, ?string $excludeId = null): bool` - Check code uniqueness
- `addMember(string $unitId, string $staffId, string $role): void` - Add unit member
- `removeMember(string $unitId, string $staffId): void` - Remove unit member
- `getMembers(string $unitId): array` - Get unit members
- `findByStaff(string $staffId): array` - Get units for staff member
- `findByCompany(string $companyId): array` - Get all units for company
- `findAll(): array` - Get all units

---

#### TransferRepositoryInterface

**Location:** `src/Contracts/TransferRepositoryInterface.php`

**Purpose:** Defines persistence operations for Transfer entities

**Key Methods:**

- `save(array $data): TransferInterface` - Create transfer
- `update(string $id, array $data): TransferInterface` - Update transfer
- `delete(string $id): bool` - Delete transfer
- `findById(string $id): ?TransferInterface` - Find by ID
- `findPendingTransfers(?string $staffId = null): array` - Get pending transfers (optionally for specific staff)
- `findByStaff(string $staffId): array` - Get all transfers for staff
- `hasPendingTransfer(string $staffId): bool` - Check if staff has pending transfer
- `findByStatus(string $status): array` - Get transfers by status
- `findAll(): array` - Get all transfers

---

### Service Interfaces

#### BackofficeManagerInterface

**Location:** `src/Contracts/BackofficeManagerInterface.php`

**Purpose:** Main orchestration interface for Backoffice operations

**Key Methods:**

##### Company Operations

```php
public function createCompany(array $data): CompanyInterface;
public function updateCompany(string $id, array $data): CompanyInterface;
public function deleteCompany(string $id): bool;
public function getCompany(string $id): ?CompanyInterface;
```

##### Office Operations

```php
public function createOffice(array $data): OfficeInterface;
public function updateOffice(string $id, array $data): OfficeInterface;
public function deleteOffice(string $id): bool;
public function getOffice(string $id): ?OfficeInterface;
```

##### Department Operations

```php
public function createDepartment(array $data): DepartmentInterface;
public function updateDepartment(string $id, array $data): DepartmentInterface;
public function deleteDepartment(string $id): bool;
public function getDepartment(string $id): ?DepartmentInterface;
```

##### Staff Operations

```php
public function createStaff(array $data): StaffInterface;
public function updateStaff(string $id, array $data): StaffInterface;
public function deleteStaff(string $id): bool;
public function getStaff(string $id): ?StaffInterface;
public function assignStaffToDepartment(string $staffId, string $departmentId, string $role, bool $isPrimary = false): void;
public function assignStaffToOffice(string $staffId, string $officeId, \DateTimeInterface $effectiveDate): void;
public function setSupervisor(string $staffId, string $supervisorId): void;
```

##### Unit Operations

```php
public function createUnit(array $data): UnitInterface;
public function updateUnit(string $id, array $data): UnitInterface;
public function deleteUnit(string $id): bool;
public function getUnit(string $id): ?UnitInterface;
public function addUnitMember(string $unitId, string $staffId, string $role): void;
public function removeUnitMember(string $unitId, string $staffId): void;
```

##### Organizational Chart

```php
public function generateOrganizationalChart(string $companyId, string $format, array $options = []): array;
public function exportOrganizationalChart(array $chartData, string $format): string;
```

---

#### TransferManagerInterface

**Location:** `src/Contracts/TransferManagerInterface.php`

**Purpose:** Interface for managing staff transfer workflows

**Methods:**

##### createTransferRequest()

```php
public function createTransferRequest(array $data): TransferInterface;
```

Create a new transfer request

**Parameters:**
- `$data['staff_id']` - Staff being transferred
- `$data['from_department_id']` - Source department
- `$data['to_department_id']` - Destination department
- `$data['from_office_id']` - Source office (optional)
- `$data['to_office_id']` - Destination office (optional)
- `$data['type']` - Transfer type (promotion, lateral_move, etc.)
- `$data['effective_date']` - When transfer takes effect
- `$data['reason']` - Transfer reason
- `$data['requested_by']` - Staff ID of requester

**Returns:** `TransferInterface` - The created transfer request

---

##### approveTransfer()

```php
public function approveTransfer(string $transferId, string $approvedBy, string $comment): TransferInterface;
```

Approve a transfer request

**Parameters:**
- `$transferId` - Transfer ID
- `$approvedBy` - Staff ID of approver
- `$comment` - Approval comment

**Returns:** `TransferInterface` - The approved transfer

---

##### rejectTransfer()

```php
public function rejectTransfer(string $transferId, string $rejectedBy, string $reason): TransferInterface;
```

Reject a transfer request

**Parameters:**
- `$transferId` - Transfer ID
- `$rejectedBy` - Staff ID of rejector
- `$reason` - Rejection reason

**Returns:** `TransferInterface` - The rejected transfer

---

##### cancelTransfer()

```php
public function cancelTransfer(string $transferId): bool;
```

Cancel a transfer request

**Parameters:**
- `$transferId` - Transfer ID

**Returns:** `bool` - True if cancelled successfully

---

##### completeTransfer()

```php
public function completeTransfer(string $transferId): TransferInterface;
```

Complete a transfer (execute the actual reassignment)

**Parameters:**
- `$transferId` - Transfer ID

**Returns:** `TransferInterface` - The completed transfer

---

##### rollbackTransfer()

```php
public function rollbackTransfer(string $transferId): TransferInterface;
```

Rollback a completed transfer

**Parameters:**
- `$transferId` - Transfer ID

**Returns:** `TransferInterface` - The rolled-back transfer

---

##### getTransfer()

```php
public function getTransfer(string $transferId): ?TransferInterface;
```

Get transfer request by ID

---

##### getPendingTransfers()

```php
public function getPendingTransfers(): array;
```

Get all pending transfer requests

**Returns:** `array<TransferInterface>` - Array of pending transfers

---

##### getStaffTransferHistory()

```php
public function getStaffTransferHistory(string $staffId): array;
```

Get transfer history for a staff member

**Returns:** `array<TransferInterface>` - Array of transfers

---

## Services

### BackofficeManager

**Location:** `src/Services/BackofficeManager.php`

**Purpose:** Main orchestration service implementing BackofficeManagerInterface

**Constructor:**

```php
public function __construct(
    private readonly CompanyRepositoryInterface $companyRepository,
    private readonly OfficeRepositoryInterface $officeRepository,
    private readonly DepartmentRepositoryInterface $departmentRepository,
    private readonly StaffRepositoryInterface $staffRepository,
    private readonly UnitRepositoryInterface $unitRepository,
)
```

**Key Features:**
- Validates business rules before persistence
- Enforces uniqueness constraints (codes, registration numbers, employee IDs)
- Prevents circular references in hierarchies
- Ensures referential integrity (active parents, supervisor chains)
- Throws descriptive exceptions for violations

---

### TransferManager

**Location:** `src/Services/TransferManager.php`

**Purpose:** Staff transfer workflow management service

**Constructor:**

```php
public function __construct(
    private readonly TransferRepositoryInterface $transferRepository,
    private readonly StaffRepositoryInterface $staffRepository,
    private readonly DepartmentRepositoryInterface $departmentRepository,
)
```

**Key Features:**
- Validates effective dates (not more than 30 days in past)
- Prevents multiple pending transfers for same staff
- Enforces approval workflows
- Handles transfer completion and rollback
- Maintains transfer history

---

## Value Objects & Enums

### CompanyStatus

**Location:** `src/ValueObjects/CompanyStatus.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `ACTIVE = 'active'` - Company is operating
- `INACTIVE = 'inactive'` - Company is dormant
- `SUSPENDED = 'suspended'` - Company operations suspended
- `DISSOLVED = 'dissolved'` - Company legally dissolved

**Methods:**

```php
public function canHaveActiveChildren(): bool;
```
Determines if company can have active subsidiaries (only ACTIVE returns true)

---

### OfficeType

**Location:** `src/ValueObjects/OfficeType.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `HEAD_OFFICE = 'head_office'` - Corporate headquarters
- `BRANCH = 'branch'` - Branch office
- `REGIONAL = 'regional'` - Regional office
- `SATELLITE = 'satellite'` - Small satellite office
- `VIRTUAL = 'virtual'` - Remote/virtual office

---

### OfficeStatus

**Location:** `src/ValueObjects/OfficeStatus.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `ACTIVE = 'active'` - Office is operational
- `INACTIVE = 'inactive'` - Office temporarily closed
- `TEMPORARY = 'temporary'` - Temporary/pop-up office
- `CLOSED = 'closed'` - Office permanently closed

---

### DepartmentType

**Location:** `src/ValueObjects/DepartmentType.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `FUNCTIONAL = 'functional'` - Function-based (Finance, HR, IT)
- `DIVISIONAL = 'divisional'` - Product/region divisions
- `MATRIX = 'matrix'` - Matrix organization
- `PROJECT_BASED = 'project_based'` - Project-based structure

---

### DepartmentStatus

**Location:** `src/ValueObjects/DepartmentStatus.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `ACTIVE = 'active'` - Department operational
- `INACTIVE = 'inactive'` - Department inactive
- `DISSOLVED = 'dissolved'` - Department dissolved

---

### StaffType

**Location:** `src/ValueObjects/StaffType.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `PERMANENT = 'permanent'` - Permanent employee
- `CONTRACT = 'contract'` - Contract employee
- `TEMPORARY = 'temporary'` - Temporary worker
- `INTERN = 'intern'` - Intern/trainee
- `CONSULTANT = 'consultant'` - External consultant

---

### StaffStatus

**Location:** `src/ValueObjects/StaffStatus.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `ACTIVE = 'active'` - Currently employed
- `INACTIVE = 'inactive'` - Temporarily inactive
- `ON_LEAVE = 'on_leave'` - On extended leave
- `TERMINATED = 'terminated'` - Employment ended

---

### UnitType

**Location:** `src/ValueObjects/UnitType.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `PROJECT_TEAM = 'project_team'` - Project team
- `COMMITTEE = 'committee'` - Committee
- `TASK_FORCE = 'task_force'` - Task force
- `WORKING_GROUP = 'working_group'` - Working group
- `CENTER_OF_EXCELLENCE = 'center_of_excellence'` - Center of Excellence

---

### UnitStatus

**Location:** `src/ValueObjects/UnitStatus.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `ACTIVE = 'active'` - Unit is active
- `INACTIVE = 'inactive'` - Unit is inactive
- `COMPLETED = 'completed'` - Unit work completed
- `DISSOLVED = 'dissolved'` - Unit dissolved

---

### TransferType

**Location:** `src/ValueObjects/TransferType.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `PROMOTION = 'promotion'` - Upward promotion
- `LATERAL_MOVE = 'lateral_move'` - Horizontal transfer
- `DEMOTION = 'demotion'` - Downward demotion
- `RELOCATION = 'relocation'` - Geographic relocation

---

### TransferStatus

**Location:** `src/ValueObjects/TransferStatus.php`

**Type:** `enum` (backed by `string`)

**Values:**
- `PENDING = 'pending'` - Awaiting approval
- `APPROVED = 'approved'` - Approved, not executed
- `REJECTED = 'rejected'` - Rejected
- `COMPLETED = 'completed'` - Transfer executed
- `CANCELLED = 'cancelled'` - Request cancelled
- `ROLLED_BACK = 'rolled_back'` - Completed transfer rolled back

---

## Exceptions

All exceptions are located in `src/Exceptions/`

### CircularReferenceException

**Purpose:** Thrown when a circular reference is detected in hierarchy

**Factory Methods:**

```php
public static function forEntity(string $entityType, string $entityId, string $parentId): self;
```

**Example:**
```php
throw new CircularReferenceException('Company', $companyId, $parentId);
```

---

### CompanyNotFoundException

**Purpose:** Thrown when a company cannot be found

**Constructor:**

```php
public function __construct(string $companyId);
```

---

### DepartmentNotFoundException

**Purpose:** Thrown when a department cannot be found

**Constructor:**

```php
public function __construct(string $departmentId);
```

---

### DuplicateCodeException

**Purpose:** Thrown when a duplicate code is detected

**Factory Methods:**

```php
public static function forEntity(string $entityType, string $code): self;
```

**Example:**
```php
throw DuplicateCodeException::forEntity('Company', 'ABC');
```

---

### InvalidHierarchyException

**Purpose:** Thrown when hierarchy rules are violated

**Factory Methods:**

```php
public static function maxDepthExceeded(string $entityType, int $maxDepth): self;
public static function invalidParent(string $entityType, string $reason): self;
```

---

### InvalidOperationException

**Purpose:** Thrown when an invalid operation is attempted

**Factory Methods:**

```php
public static function inactiveEntity(string $entityType, string $entityId): self;
public static function hasActiveChildren(string $entityType, string $entityId): self;
public static function hasActiveStaff(string $entityType, string $entityId): self;
```

---

### InvalidTransferException

**Purpose:** Thrown when transfer rules are violated

**Factory Methods:**

```php
public static function effectiveDateTooFarInPast(\DateTimeInterface $date): self;
public static function pendingTransferExists(string $staffId): self;
public static function invalidStatus(string $currentStatus, string $action): self;
```

---

### OfficeNotFoundException

**Purpose:** Thrown when an office cannot be found

---

### StaffNotFoundException

**Purpose:** Thrown when a staff member cannot be found

---

### TransferNotFoundException

**Purpose:** Thrown when a transfer cannot be found

---

### UnitNotFoundException

**Purpose:** Thrown when a unit cannot be found

---

## Usage Patterns

### Pattern 1: Hierarchical Queries

**Get all descendants (nested set):**

```php
$departmentRepo = app(DepartmentRepositoryInterface::class);

// Get all sub-departments at all levels
$allDescendants = $departmentRepo->getDescendants($financeId);

// Get immediate children only
$children = $departmentRepo->getChildren($financeId);

// Get parent chain
$ancestors = $departmentRepo->getAncestors($accountsPayableId);
```

**Get company subsidiaries:**

```php
$companyRepo = app(CompanyRepositoryInterface::class);

// Get direct subsidiaries
$subsidiaries = $companyRepo->getSubsidiaries($holdingsId);

// Get parent chain
$parentChain = $companyRepo->getParentChain($subsidiaryId);
```

---

### Pattern 2: Matrix Organizations

**Create cross-functional unit:**

```php
$manager = app(BackofficeManagerInterface::class);

// Create committee
$committee = $manager->createUnit([
    'company_id' => $companyId,
    'code' => 'EXECOM',
    'name' => 'Executive Committee',
    'type' => 'committee',
    'status' => 'active',
]);

// Add members from different departments
$manager->addUnitMember($committee->getId(), $ceoId, 'leader');
$manager->addUnitMember($committee->getId(), $cfoId, 'member');
$manager->addUnitMember($committee->getId(), $cooId, 'member');
$manager->addUnitMember($committee->getId(), $ctoId, 'member');

// Get all units a staff belongs to
$staffUnits = $unitRepo->findByStaff($cfoId);
```

---

### Pattern 3: Transfer Workflows

**Complete transfer workflow:**

```php
$transferManager = app(TransferManagerInterface::class);

// 1. Create request
$transfer = $transferManager->createTransferRequest([
    'staff_id' => $staffId,
    'from_department_id' => $currentDeptId,
    'to_department_id' => $newDeptId,
    'type' => 'promotion',
    'effective_date' => new \DateTime('+30 days'),
    'reason' => 'Promoted to Senior Accountant',
    'requested_by' => $managerId,
]);

// 2. Approve
$transfer = $transferManager->approveTransfer(
    transferId: $transfer->getId(),
    approvedBy: $directorId,
    comment: 'Well deserved promotion. Approved.'
);

// 3. Complete on effective date
$transfer = $transferManager->completeTransfer($transfer->getId());

// 4. View history
$history = $transferManager->getStaffTransferHistory($staffId);
```

---

### Pattern 4: Organizational Reporting

**Headcount by department:**

```php
$deptRepo = app(DepartmentRepositoryInterface::class);
$staffRepo = app(StaffRepositoryInterface::class);

$departments = $deptRepo->findByCompany($companyId);

$headcount = [];
foreach ($departments as $dept) {
    $staff = $staffRepo->findByDepartment($dept->getId());
    $headcount[$dept->getName()] = count($staff);
}
```

**Span of control report:**

```php
$staffRepo = app(StaffRepositoryInterface::class);

$allStaff = $staffRepo->findAll();

$spanOfControl = [];
foreach ($allStaff as $staff) {
    if ($staff->isActive()) {
        $subordinates = $staffRepo->getSubordinates($staff->getId());
        $spanOfControl[$staff->getFullName()] = count($subordinates);
    }
}

// Find managers with large spans
$largeSpans = array_filter($spanOfControl, fn($count) => $count > 10);
```

---

This API reference provides comprehensive documentation for all interfaces, services, value objects, and exceptions in the Nexus\Backoffice package.
