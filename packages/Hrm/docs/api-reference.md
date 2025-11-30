# API Reference: Hrm

## Interfaces

### EmployeeInterface

**Location:** `src/Contracts/EmployeeInterface.php`

**Purpose:** Represents an employee entity in the HRM domain.

**Methods:**

#### getId()
```php
public function getId(): string;
```
Returns the employee's unique identifier (ULID).

#### getTenantId()
```php
public function getTenantId(): string;
```
Returns the tenant identifier this employee belongs to.

#### getEmployeeCode()
```php
public function getEmployeeCode(): string;
```
Returns the unique employee code (e.g., "EMP001").

#### getFirstName() / getLastName() / getFullName()
```php
public function getFirstName(): string;
public function getLastName(): string;
public function getFullName(): string;
```
Returns employee name components.

#### getEmail()
```php
public function getEmail(): string;
```
Returns employee's email address.

#### getPhoneNumber()
```php
public function getPhoneNumber(): ?string;
```
Returns employee's phone number (nullable).

#### getDateOfBirth()
```php
public function getDateOfBirth(): \DateTimeInterface;
```
Returns employee's date of birth.

#### getHireDate()
```php
public function getHireDate(): \DateTimeInterface;
```
Returns the date employee was hired.

#### getConfirmationDate()
```php
public function getConfirmationDate(): ?\DateTimeInterface;
```
Returns the date employee was confirmed (after probation), or null if still probationary.

#### getTerminationDate()
```php
public function getTerminationDate(): ?\DateTimeInterface;
```
Returns termination date, or null if employee is active.

#### getStatus()
```php
public function getStatus(): string;
```
Returns employee status (EmployeeStatus enum: probationary, confirmed, resigned, terminated, retired, suspended).

#### getManagerId() / getDepartmentId() / getOfficeId()
```php
public function getManagerId(): ?string;
public function getDepartmentId(): ?string;
public function getOfficeId(): ?string;
```
Returns organizational assignment identifiers.

#### getJobTitle() / getEmploymentType()
```php
public function getJobTitle(): ?string;
public function getEmploymentType(): string;
```
Returns job title and employment type (EmploymentType enum: full_time, part_time, contract, intern, freelance).

---

### EmployeeRepositoryInterface

**Location:** `src/Contracts/EmployeeRepositoryInterface.php`

**Purpose:** Repository contract for employee persistence operations.

**Methods:**

#### findById()
```php
public function findById(string $id): ?EmployeeInterface;
```
**Description:** Find employee by unique identifier.

**Parameters:**
- `$id` (string) - Employee ULID

**Returns:** `EmployeeInterface|null` - Employee or null if not found

**Example:**
```php
$employee = $employeeRepository->findById('emp_01HZXXX...');
```

#### findByEmployeeCode()
```php
public function findByEmployeeCode(string $tenantId, string $employeeCode): ?EmployeeInterface;
```
**Description:** Find employee by employee code within tenant.

**Parameters:**
- `$tenantId` (string) - Tenant ULID
- `$employeeCode` (string) - Unique employee code (e.g., "EMP001")

**Returns:** `EmployeeInterface|null`

#### findByEmail()
```php
public function findByEmail(string $tenantId, string $email): ?EmployeeInterface;
```
**Description:** Find employee by email address within tenant.

#### getAll()
```php
public function getAll(string $tenantId, array $filters = []): array;
```
**Description:** Get all employees for a tenant with optional filters.

**Parameters:**
- `$tenantId` (string) - Tenant ULID
- `$filters` (array) - Optional filters (e.g., `['status' => 'confirmed', 'department_id' => 'dept_xxx']`)

**Returns:** `array<EmployeeInterface>`

#### save()
```php
public function save(EmployeeInterface $employee): void;
```
**Description:** Persist employee entity.

**Throws:**
- `EmployeeDuplicateException` - If employee code or email already exists
- `EmployeeValidationException` - If validation fails

#### delete()
```php
public function delete(string $id): void;
```
**Description:** Delete employee by ID.

---

### LeaveInterface

**Location:** `src/Contracts/LeaveInterface.php`

**Purpose:** Represents a leave request entity.

**Methods:**

#### getId() / getTenantId() / getEmployeeId()
```php
public function getId(): string;
public function getTenantId(): string;
public function getEmployeeId(): string;
```
Returns leave request identifiers.

#### getLeaveTypeId()
```php
public function getLeaveTypeId(): string;
```
Returns the leave type identifier (annual, medical, emergency, etc.).

#### getStartDate() / getEndDate()
```php
public function getStartDate(): \DateTimeInterface;
public function getEndDate(): \DateTimeInterface;
```
Returns leave period dates.

#### getDaysRequested()
```php
public function getDaysRequested(): float;
```
Returns number of days requested (supports half-days: 0.5, 1.0, 1.5, etc.).

#### getReason()
```php
public function getReason(): ?string;
```
Returns leave request reason (nullable).

#### getStatus()
```php
public function getStatus(): string;
```
Returns leave status (LeaveStatus enum: pending, approved, rejected, cancelled).

#### getApprovedBy() / getApprovedAt()
```php
public function getApprovedBy(): ?string;
public function getApprovedAt(): ?\DateTimeInterface;
```
Returns approver ID and approval timestamp.

#### getRejectionReason()
```php
public function getRejectionReason(): ?string;
```
Returns reason for rejection (if rejected).

---

### LeaveRepositoryInterface

**Location:** `src/Contracts/LeaveRepositoryInterface.php`

**Purpose:** Repository contract for leave request persistence.

**Methods:**

#### findById()
```php
public function findById(string $id): ?LeaveInterface;
```

#### findByEmployee()
```php
public function findByEmployee(string $employeeId, array $filters = []): array;
```
**Description:** Get all leave requests for an employee with optional filters.

**Parameters:**
- `$employeeId` (string) - Employee ULID
- `$filters` (array) - Optional (e.g., `['status' => 'approved', 'year' => 2025]`)

#### findOverlapping()
```php
public function findOverlapping(
    string $employeeId,
    \DateTimeInterface $startDate,
    \DateTimeInterface $endDate
): array;
```
**Description:** Find leave requests that overlap with given date range for employee.

**Used for:** Overlap validation before creating new leave request.

#### save()
```php
public function save(LeaveInterface $leave): void;
```

**Throws:**
- `LeaveOverlapException` - If leave overlaps with existing request
- `LeaveValidationException` - If validation fails

---

### LeaveBalanceInterface

**Location:** `src/Contracts/LeaveBalanceInterface.php`

**Purpose:** Represents leave balance for employee and leave type.

**Methods:**

#### getEmployeeId() / getLeaveTypeId()
```php
public function getEmployeeId(): string;
public function getLeaveTypeId(): string;
```

#### getEntitled()
```php
public function getEntitled(): float;
```
Returns annual entitlement (e.g., 14 days).

#### getAccrued()
```php
public function getAccrued(): float;
```
Returns accrued balance (progressive accumulation).

#### getUsed()
```php
public function getUsed(): float;
```
Returns used balance (approved leave requests).

#### getAvailable()
```php
public function getAvailable(): float;
```
Returns available balance (accrued - used).

#### getCarriedForward()
```php
public function getCarriedForward(): float;
```
Returns carried forward balance from previous year.

---

### AttendanceInterface

**Location:** `src/Contracts/AttendanceInterface.php`

**Purpose:** Represents an attendance record (clock-in/clock-out).

**Methods:**

#### getId() / getTenantId() / getEmployeeId()
```php
public function getId(): string;
public function getTenantId(): string;
public function getEmployeeId(): string;
```

#### getClockInTime() / getClockOutTime()
```php
public function getClockInTime(): \DateTimeInterface;
public function getClockOutTime(): ?\DateTimeInterface;
```
Returns clock-in timestamp (required) and clock-out timestamp (nullable until clocked out).

#### getLocation()
```php
public function getLocation(): ?string;
```
Returns location name (e.g., "Office HQ", "Remote").

#### getLatitude() / getLongitude()
```php
public function getLatitude(): ?float;
public function getLongitude(): ?float;
```
Returns GPS coordinates (nullable).

#### getHoursWorked()
```php
public function getHoursWorked(): ?float;
```
Returns calculated hours worked (null until clocked out).

#### getBreakMinutes()
```php
public function getBreakMinutes(): ?int;
```
Returns break time in minutes.

#### getOvertimeHours()
```php
public function getOvertimeHours(): ?float;
```
Returns overtime hours (based on threshold policy).

#### getStatus()
```php
public function getStatus(): string;
```
Returns attendance status (AttendanceStatus enum: active, completed, corrected).

---

### AttendanceRepositoryInterface

**Location:** `src/Contracts/AttendanceRepositoryInterface.php`

**Purpose:** Repository contract for attendance persistence.

**Methods:**

#### findById()
```php
public function findById(string $id): ?AttendanceInterface;
```

#### findActiveByEmployee()
```php
public function findActiveByEmployee(string $employeeId): ?AttendanceInterface;
```
**Description:** Find active (not clocked out) attendance record for employee.

**Returns:** `AttendanceInterface|null` - Active record or null

**Used for:** Overlap prevention (prevent multiple clock-ins).

#### findByEmployeeAndDateRange()
```php
public function findByEmployeeAndDateRange(
    string $employeeId,
    \DateTimeInterface $startDate,
    \DateTimeInterface $endDate
): array;
```
**Description:** Get attendance records for employee within date range.

**Used for:** Monthly summaries, payroll integration.

#### save()
```php
public function save(AttendanceInterface $attendance): void;
```

**Throws:**
- `AttendanceDuplicateException` - If active record already exists
- `AttendanceValidationException` - If validation fails

---

### PerformanceReviewInterface

**Location:** `src/Contracts/PerformanceReviewInterface.php`

**Purpose:** Represents a performance review entity.

**Methods:**

#### getId() / getTenantId() / getEmployeeId()
```php
public function getId(): string;
public function getTenantId(): string;
public function getEmployeeId(): string;
```

#### getReviewCycleId()
```php
public function getReviewCycleId(): string;
```
Returns the review cycle identifier (e.g., "2025-Q4", "Annual-2025").

#### getReviewType()
```php
public function getReviewType(): string;
```
Returns review type (ReviewType enum: annual, probation, project_based, 360_feedback).

#### getReviewerId()
```php
public function getReviewerId(): string;
```
Returns the reviewer's employee ID (usually manager).

#### getSelfAssessmentScore() / getManagerAssessmentScore()
```php
public function getSelfAssessmentScore(): ?float;
public function getManagerAssessmentScore(): ?float;
```
Returns assessment scores (nullable until completed).

#### getFinalRating()
```php
public function getFinalRating(): ?float;
```
Returns final rating (after calibration).

#### getStatus()
```php
public function getStatus(): string;
```
Returns review status (ReviewStatus enum: draft, self_assessment_pending, manager_review_pending, completed).

#### getReviewPeriodStart() / getReviewPeriodEnd()
```php
public function getReviewPeriodStart(): \DateTimeInterface;
public function getReviewPeriodEnd(): \DateTimeInterface;
```
Returns the period being reviewed.

---

### DisciplinaryInterface

**Location:** `src/Contracts/DisciplinaryInterface.php`

**Purpose:** Represents a disciplinary case.

**Methods:**

#### getId() / getTenantId() / getEmployeeId()
```php
public function getId(): string;
public function getTenantId(): string;
public function getEmployeeId(): string;
```

#### getCaseNumber()
```php
public function getCaseNumber(): string;
```
Returns unique case number (e.g., "DISC-2025-001").

#### getIncidentDate()
```php
public function getIncidentDate(): \DateTimeInterface;
```
Returns date of incident.

#### getSeverity()
```php
public function getSeverity(): string;
```
Returns severity level (DisciplinarySeverity enum: minor, moderate, major, severe).

#### getDescription()
```php
public function getDescription(): string;
```
Returns incident description.

#### getActionTaken()
```php
public function getActionTaken(): ?string;
```
Returns action taken (e.g., "Verbal warning", "Written warning", "Suspension").

#### getStatus()
```php
public function getStatus(): string;
```
Returns case status (DisciplinaryStatus enum: investigation, hearing_scheduled, resolved, appealed).

#### getInvestigatorId()
```php
public function getInvestigatorId(): ?string;
```
Returns investigator's employee ID.

---

### TrainingInterface

**Location:** `src/Contracts/TrainingInterface.php`

**Purpose:** Represents a training program.

**Methods:**

#### getId() / getTenantId()
```php
public function getId(): string;
public function getTenantId(): string;
```

#### getTrainingCode()
```php
public function getTrainingCode(): string;
```
Returns unique training code (e.g., "TRN-001").

#### getTitle() / getDescription()
```php
public function getTitle(): string;
public function getDescription(): ?string;
```
Returns training title and description.

#### getProvider()
```php
public function getProvider(): ?string;
```
Returns training provider (e.g., "Internal", "LinkedIn Learning", "Udemy").

#### getDurationHours()
```php
public function getDurationHours(): ?float;
```
Returns training duration in hours.

#### getCertificationAwarded()
```php
public function getCertificationAwarded(): bool;
```
Returns whether training awards certification.

#### getStatus()
```php
public function getStatus(): string;
```
Returns training status (TrainingStatus enum: scheduled, ongoing, completed, cancelled).

---

### TrainingEnrollmentInterface

**Location:** `src/Contracts/TrainingEnrollmentInterface.php`

**Purpose:** Represents employee enrollment in training program.

**Methods:**

#### getId() / getTenantId() / getEmployeeId() / getTrainingId()
```php
public function getId(): string;
public function getTenantId(): string;
public function getEmployeeId(): string;
public function getTrainingId(): string;
```

#### getEnrollmentDate()
```php
public function getEnrollmentDate(): \DateTimeInterface;
```
Returns date employee enrolled.

#### getCompletionDate()
```php
public function getCompletionDate(): ?\DateTimeInterface;
```
Returns completion date (nullable until completed).

#### getCertificationExpiryDate()
```php
public function getCertificationExpiryDate(): ?\DateTimeInterface;
```
Returns certification expiry date (if certification has expiry).

#### getStatus()
```php
public function getStatus(): string;
```
Returns enrollment status (EnrollmentStatus enum: enrolled, in_progress, completed, failed, cancelled).

---

## Services

### EmployeeManager

**Location:** `src/Services/EmployeeManager.php`

**Purpose:** Main orchestration service for employee lifecycle management.

**Constructor Dependencies:**
- `EmployeeRepositoryInterface` - Employee persistence
- `OrganizationServiceContract` - Backoffice integration for manager/department

**Public Methods:**

#### createEmployee()
```php
public function createEmployee(array $data): EmployeeInterface;
```
**Description:** Create new employee with validation.

**Parameters:**
- `$data` (array) - Employee data
  ```php
  [
      'employee_code' => 'EMP001',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'email' => 'john.doe@example.com',
      'date_of_birth' => '1990-01-01',
      'hire_date' => '2025-01-01',
      'employment_type' => EmploymentType::full_time,
  ]
  ```

**Returns:** `EmployeeInterface` - Created employee

**Throws:**
- `EmployeeDuplicateException` - If employee code or email exists
- `EmployeeValidationException` - If validation fails

#### updateEmployee()
```php
public function updateEmployee(string $id, array $data): EmployeeInterface;
```
**Description:** Update employee details.

#### confirmEmployee()
```php
public function confirmEmployee(string $id, \DateTimeInterface $confirmationDate): EmployeeInterface;
```
**Description:** Confirm employee after probation period.

**Validation:**
- Probation period must be completed
- Employee status must be 'probationary'

**Effect:**
- Updates status to 'confirmed'
- Sets confirmation date
- Triggers permanent leave entitlements

#### terminateEmployee()
```php
public function terminateEmployee(
    string $id,
    \DateTimeInterface $terminationDate,
    string $reason
): EmployeeInterface;
```
**Description:** Terminate employee.

**Validation:**
- Cannot terminate if under disciplinary investigation
- Must calculate final settlement (unused leave encashment)

**Effect:**
- Updates status to 'terminated'
- Sets termination date
- Triggers final settlement calculation

---

### LeaveManager

**Location:** `src/Services/LeaveManager.php`

**Purpose:** Leave request and balance management.

**Constructor Dependencies:**
- `LeaveRepositoryInterface`
- `LeaveBalanceRepositoryInterface`
- `LeaveTypeRepositoryInterface`

**Public Methods:**

#### createLeaveRequest()
```php
public function createLeaveRequest(array $data): LeaveInterface;
```
**Description:** Create leave request with balance validation.

**Parameters:**
```php
[
    'employee_id' => 'emp_xxx',
    'leave_type_id' => 'lt_xxx',
    'start_date' => '2025-06-01',
    'end_date' => '2025-06-05',
    'reason' => 'Annual vacation',
]
```

**Validation:**
- Balance check (sufficient available balance)
- Overlap check (no existing leave in same period)
- Business day calculation (excludes weekends/holidays)

**Throws:**
- `LeaveBalanceValidationException` - Insufficient balance
- `LeaveOverlapException` - Overlapping leave exists

#### approveLeaveRequest()
```php
public function approveLeaveRequest(string $leaveId, string $approverId): LeaveInterface;
```
**Description:** Approve leave request.

**Effect:**
- Updates status to 'approved'
- Deducts from leave balance
- Records approver and timestamp

#### rejectLeaveRequest()
```php
public function rejectLeaveRequest(string $leaveId, string $reason): LeaveInterface;
```
**Description:** Reject leave request.

**Effect:**
- Updates status to 'rejected'
- Restores reserved balance
- Records rejection reason

#### getLeaveBalance()
```php
public function getLeaveBalance(string $employeeId, string $leaveTypeId): LeaveBalanceInterface;
```
**Description:** Get current leave balance for employee and leave type.

---

### AttendanceManager

**Location:** `src/Services/AttendanceManager.php`

**Purpose:** Attendance tracking with clock-in/out operations.

**Constructor Dependencies:**
- `AttendanceRepositoryInterface`

**Public Methods:**

#### clockIn()
```php
public function clockIn(string $employeeId, array $data = []): AttendanceInterface;
```
**Description:** Clock in employee.

**Parameters:**
```php
[
    'location' => 'Office HQ',
    'latitude' => 3.1390,
    'longitude' => 101.6869,
]
```

**Validation:**
- No active (not clocked out) attendance record exists

**Throws:**
- `AttendanceDuplicateException` - Employee already clocked in

#### clockOut()
```php
public function clockOut(string $attendanceId, array $data = []): AttendanceInterface;
```
**Description:** Clock out employee.

**Effect:**
- Calculates hours worked
- Calculates overtime (if threshold exceeded)
- Deducts break time

**Parameters:**
```php
[
    'break_minutes' => 60,
]
```

#### getMonthlyAttendanceSummary()
```php
public function getMonthlyAttendanceSummary(string $employeeId, int $year, int $month): array;
```
**Description:** Get attendance summary for month.

**Returns:**
```php
[
    'total_days' => 22,
    'present_days' => 20,
    'absent_days' => 2,
    'total_hours' => 176.5,
    'overtime_hours' => 8.5,
    'late_arrivals' => 3,
]
```

---

### PerformanceReviewManager

**Location:** `src/Services/PerformanceReviewManager.php`

**Purpose:** Performance review workflow management.

**Public Methods:**

#### createPerformanceReview()
```php
public function createPerformanceReview(array $data): PerformanceReviewInterface;
```

#### submitSelfAssessment()
```php
public function submitSelfAssessment(string $reviewId, array $assessment): PerformanceReviewInterface;
```
**Description:** Submit self-assessment.

**Effect:**
- Updates self-assessment score
- Transitions status to 'manager_review_pending'

#### submitManagerReview()
```php
public function submitManagerReview(string $reviewId, array $assessment): PerformanceReviewInterface;
```
**Description:** Submit manager review.

**Validation:**
- Self-assessment must be completed first

---

### DisciplinaryManager

**Location:** `src/Services/DisciplinaryManager.php`

**Purpose:** Disciplinary case lifecycle management.

**Public Methods:**

#### createDisciplinaryCase()
```php
public function createDisciplinaryCase(array $data): DisciplinaryInterface;
```

#### assignInvestigator()
```php
public function assignInvestigator(string $caseId, string $investigatorId): DisciplinaryInterface;
```

#### resolveCase()
```php
public function resolveCase(string $caseId, array $resolution): DisciplinaryInterface;
```
**Description:** Resolve disciplinary case.

**Parameters:**
```php
[
    'action_taken' => 'Written warning',
    'resolution_notes' => '...',
]
```

---

### TrainingManager

**Location:** `src/Services/TrainingManager.php`

**Purpose:** Training program and enrollment management.

**Public Methods:**

#### createTrainingProgram()
```php
public function createTrainingProgram(array $data): TrainingInterface;
```

#### enrollEmployee()
```php
public function enrollEmployee(string $employeeId, string $trainingId): TrainingEnrollmentInterface;
```
**Validation:**
- No duplicate enrollment
- Training capacity check (if applicable)

**Throws:**
- `TrainingEnrollmentDuplicateException`

#### markTrainingCompleted()
```php
public function markTrainingCompleted(string $enrollmentId, array $data = []): TrainingEnrollmentInterface;
```
**Effect:**
- Sets completion date
- Awards certification (if applicable)
- Updates status to 'completed'

---

## Value Objects (Enums)

### EmployeeStatus

**Location:** `src/ValueObjects/EmployeeStatus.php`

**Purpose:** Employee lifecycle states.

**Cases:**
- `probationary` - Employee in probation period
- `confirmed` - Permanent employee (after probation)
- `resigned` - Employee resigned (serving notice)
- `terminated` - Employment terminated
- `retired` - Employee retired
- `suspended` - Temporarily suspended

### EmploymentType

**Location:** `src/ValueObjects/EmploymentType.php`

**Cases:**
- `full_time` - Full-time permanent
- `part_time` - Part-time employee
- `contract` - Fixed-term contract
- `intern` - Internship
- `freelance` - Freelancer/consultant

### LeaveStatus

**Location:** `src/ValueObjects/LeaveStatus.php`

**Cases:**
- `pending` - Awaiting approval
- `approved` - Approved by manager
- `rejected` - Rejected by manager
- `cancelled` - Cancelled by employee

### AttendanceStatus

**Location:** `src/ValueObjects/AttendanceStatus.php`

**Cases:**
- `active` - Clocked in, not clocked out
- `completed` - Clocked in and out
- `corrected` - Manually corrected by HR

### ReviewStatus

**Location:** `src/ValueObjects/ReviewStatus.php`

**Cases:**
- `draft` - Review created, not started
- `self_assessment_pending` - Awaiting self-assessment
- `manager_review_pending` - Awaiting manager review
- `completed` - Review finalized

### DisciplinarySeverity

**Location:** `src/ValueObjects/DisciplinarySeverity.php`

**Cases:**
- `minor` - Minor infraction (verbal warning)
- `moderate` - Moderate issue (written warning)
- `major` - Major violation (suspension)
- `severe` - Severe misconduct (termination)

### TrainingStatus

**Location:** `src/ValueObjects/TrainingStatus.php`

**Cases:**
- `scheduled` - Training scheduled
- `ongoing` - Training in progress
- `completed` - Training completed
- `cancelled` - Training cancelled

---

## Exceptions

All exceptions extend `Nexus\Hrm\Exceptions\HrmException`.

### EmployeeNotFoundException

**Location:** `src/Exceptions/EmployeeNotFoundException.php`

**Purpose:** Thrown when employee not found.

**Factory Method:**
```php
EmployeeNotFoundException::withId(string $id): self
```

**Example:**
```php
throw EmployeeNotFoundException::withId('emp_xxx');
// Message: "Employee with ID 'emp_xxx' not found"
```

### EmployeeDuplicateException

**Purpose:** Thrown when employee code or email already exists.

**Factory Methods:**
```php
EmployeeDuplicateException::withCode(string $code): self
EmployeeDuplicateException::withEmail(string $email): self
```

### EmployeeValidationException

**Purpose:** Thrown when employee validation fails.

**Factory Method:**
```php
EmployeeValidationException::withMessage(string $message): self
```

### LeaveBalanceValidationException

**Purpose:** Thrown when leave balance is insufficient.

**Factory Method:**
```php
LeaveBalanceValidationException::insufficientBalance(
    float $required,
    float $available
): self
```

### LeaveOverlapException

**Purpose:** Thrown when leave overlaps with existing request.

**Factory Method:**
```php
LeaveOverlapException::forEmployee(string $employeeId): self
```

### AttendanceDuplicateException

**Purpose:** Thrown when employee already has active attendance record.

**Factory Method:**
```php
AttendanceDuplicateException::activeRecordExists(string $employeeId): self
```

---

## Usage Patterns

### Pattern 1: Employee Lifecycle Management

```php
// Create employee
$employee = $employeeManager->createEmployee([...]);

// After probation
$employeeManager->confirmEmployee($employee->getId(), new \DateTimeImmutable());

// On resignation
$employeeManager->terminateEmployee(
    $employee->getId(),
    new \DateTimeImmutable('+30 days'),
    'Voluntary resignation'
);
```

### Pattern 2: Leave Request Workflow

```php
// Employee requests leave
$leave = $leaveManager->createLeaveRequest([...]);

// Manager approves
$leaveManager->approveLeaveRequest($leave->getId(), $managerId);

// OR Manager rejects
$leaveManager->rejectLeaveRequest($leave->getId(), 'Insufficient coverage');
```

### Pattern 3: Attendance Tracking

```php
// Clock in
$attendance = $attendanceManager->clockIn($employeeId, [
    'location' => 'Office',
    'latitude' => 3.1390,
    'longitude' => 101.6869,
]);

// Clock out (8 hours later)
$attendanceManager->clockOut($attendance->getId(), [
    'break_minutes' => 60,
]);
```

### Pattern 4: Performance Review Cycle

```php
// Create review
$review = $performanceReviewManager->createPerformanceReview([...]);

// Employee self-assessment
$performanceReviewManager->submitSelfAssessment($review->getId(), [
    'score' => 4.5,
    'comments' => '...',
]);

// Manager review
$performanceReviewManager->submitManagerReview($review->getId(), [
    'score' => 4.2,
    'feedback' => '...',
]);
```

---

**API Documentation Version:** 1.0.0  
**Last Updated:** 2025-11-25  
**Package Version:** 1.0.0
