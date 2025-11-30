# Integration Guide: Hrm

This guide shows how to integrate the Hrm package into your application with Laravel and Symfony examples.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/hrm:"*@dev"
```

### Step 2: Create Database Migrations

Create all necessary tables for HRM entities.

**Migration: employees table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('employee_code', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255);
            $table->string('phone_number', 50)->nullable();
            $table->date('date_of_birth');
            $table->date('hire_date');
            $table->date('confirmation_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('status', 50); // EmployeeStatus enum
            $table->string('employment_type', 50); // EmploymentType enum
            $table->string('job_title', 255)->nullable();
            $table->string('manager_id', 26)->nullable();
            $table->string('department_id', 26)->nullable();
            $table->string('office_id', 26)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'employee_code']);
            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'department_id']);
            $table->index(['tenant_id', 'manager_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
```

**Migration: employment_contracts table**

```php
Schema::create('employment_contracts', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('employee_id', 26);
    $table->string('contract_type', 50); // ContractType enum
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->integer('probation_months')->nullable();
    $table->string('position_title', 255);
    $table->decimal('salary', 15, 2);
    $table->string('pay_frequency', 50); // PayFrequency enum
    $table->json('benefits')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    $table->index(['tenant_id', 'employee_id']);
});
```

**Migration: leave_types table**

```php
Schema::create('leave_types', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('code', 50);
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->decimal('annual_entitlement', 8, 2);
    $table->boolean('is_paid')->default(true);
    $table->boolean('allow_negative_balance')->default(false);
    $table->boolean('allow_carry_forward')->default(false);
    $table->integer('max_carry_forward_days')->nullable();
    $table->boolean('requires_documentation')->default(false);
    $table->integer('documentation_threshold_days')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['tenant_id', 'code']);
});
```

**Migration: leave_balances table**

```php
Schema::create('leave_balances', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('employee_id', 26);
    $table->string('leave_type_id', 26);
    $table->year('year');
    $table->decimal('entitled', 8, 2)->default(0);
    $table->decimal('accrued', 8, 2)->default(0);
    $table->decimal('used', 8, 2)->default(0);
    $table->decimal('carried_forward', 8, 2)->default(0);
    $table->decimal('adjusted', 8, 2)->default(0);
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
    $table->unique(['employee_id', 'leave_type_id', 'year']);
    $table->index(['tenant_id', 'employee_id']);
});
```

**Migration: leave_requests table**

```php
Schema::create('leave_requests', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('employee_id', 26);
    $table->string('leave_type_id', 26);
    $table->date('start_date');
    $table->date('end_date');
    $table->decimal('days_requested', 8, 2);
    $table->text('reason')->nullable();
    $table->string('status', 50); // LeaveStatus enum
    $table->string('approved_by', 26)->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
    $table->index(['tenant_id', 'employee_id', 'status']);
    $table->index(['start_date', 'end_date']);
});
```

**Migration: attendance_records table**

```php
Schema::create('attendance_records', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('employee_id', 26);
    $table->dateTime('clock_in_time');
    $table->dateTime('clock_out_time')->nullable();
    $table->string('location', 255)->nullable();
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    $table->decimal('hours_worked', 8, 2)->nullable();
    $table->integer('break_minutes')->nullable();
    $table->decimal('overtime_hours', 8, 2)->nullable();
    $table->string('status', 50); // AttendanceStatus enum
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    $table->index(['tenant_id', 'employee_id', 'clock_in_time']);
});
```

**Migration: performance_reviews table**

```php
Schema::create('performance_reviews', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('employee_id', 26);
    $table->string('review_cycle_id', 50);
    $table->string('review_type', 50); // ReviewType enum
    $table->string('reviewer_id', 26);
    $table->date('review_period_start');
    $table->date('review_period_end');
    $table->decimal('self_assessment_score', 5, 2)->nullable();
    $table->text('self_assessment_comments')->nullable();
    $table->decimal('manager_assessment_score', 5, 2)->nullable();
    $table->text('manager_assessment_comments')->nullable();
    $table->decimal('final_rating', 5, 2)->nullable();
    $table->string('status', 50); // ReviewStatus enum
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    $table->index(['tenant_id', 'employee_id', 'review_cycle_id']);
});
```

**Migration: disciplinary_cases table**

```php
Schema::create('disciplinary_cases', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('employee_id', 26);
    $table->string('case_number', 50)->unique();
    $table->date('incident_date');
    $table->string('severity', 50); // DisciplinarySeverity enum
    $table->text('description');
    $table->string('investigator_id', 26)->nullable();
    $table->text('evidence')->nullable();
    $table->string('action_taken', 255)->nullable();
    $table->text('resolution_notes')->nullable();
    $table->string('status', 50); // DisciplinaryStatus enum
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    $table->index(['tenant_id', 'employee_id']);
});
```

**Migration: training_programs table**

```php
Schema::create('training_programs', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('training_code', 50);
    $table->string('title', 255);
    $table->text('description')->nullable();
    $table->string('provider', 255)->nullable();
    $table->decimal('duration_hours', 8, 2)->nullable();
    $table->boolean('certification_awarded')->default(false);
    $table->integer('certification_validity_months')->nullable();
    $table->string('status', 50); // TrainingStatus enum
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->unique(['tenant_id', 'training_code']);
});
```

**Migration: training_enrollments table**

```php
Schema::create('training_enrollments', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('employee_id', 26);
    $table->string('training_id', 26);
    $table->date('enrollment_date');
    $table->date('completion_date')->nullable();
    $table->date('certification_expiry_date')->nullable();
    $table->string('status', 50); // EnrollmentStatus enum
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    $table->foreign('training_id')->references('id')->on('training_programs')->onDelete('cascade');
    $table->unique(['employee_id', 'training_id']);
    $table->index(['tenant_id', 'employee_id']);
});
```

### Step 3: Create Eloquent Models

**Employee Model**

```php
<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\ValueObjects\EmployeeStatus;
use Nexus\Hrm\ValueObjects\EmploymentType;

class Employee extends Model implements EmployeeInterface
{
    protected $fillable = [
        'id', 'tenant_id', 'employee_code', 'first_name', 'last_name',
        'email', 'phone_number', 'date_of_birth', 'hire_date',
        'confirmation_date', 'termination_date', 'status', 'employment_type',
        'job_title', 'manager_id', 'department_id', 'office_id', 'metadata'
    ];

    protected $casts = [
        'status' => EmployeeStatus::class,
        'employment_type' => EmploymentType::class,
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'confirmation_date' => 'date',
        'termination_date' => 'date',
        'metadata' => 'array',
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getEmployeeCode(): string
    {
        return $this->employee_code;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getFullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function getDateOfBirth(): \DateTimeInterface
    {
        return \DateTimeImmutable::createFromMutable($this->date_of_birth);
    }

    public function getHireDate(): \DateTimeInterface
    {
        return \DateTimeImmutable::createFromMutable($this->hire_date);
    }

    public function getConfirmationDate(): ?\DateTimeInterface
    {
        return $this->confirmation_date 
            ? \DateTimeImmutable::createFromMutable($this->confirmation_date)
            : null;
    }

    public function getTerminationDate(): ?\DateTimeInterface
    {
        return $this->termination_date 
            ? \DateTimeImmutable::createFromMutable($this->termination_date)
            : null;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getEmploymentType(): string
    {
        return $this->employment_type->value;
    }

    public function getManagerId(): ?string
    {
        return $this->manager_id;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getOfficeId(): ?string
    {
        return $this->office_id;
    }

    public function getJobTitle(): ?string
    {
        return $this->job_title;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    // Relationships
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
```

**LeaveRequest Model** (Similar pattern)

```php
<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Nexus\Hrm\Contracts\LeaveInterface;
use Nexus\Hrm\ValueObjects\LeaveStatus;

class LeaveRequest extends Model implements LeaveInterface
{
    protected $fillable = [
        'id', 'tenant_id', 'employee_id', 'leave_type_id',
        'start_date', 'end_date', 'days_requested', 'reason',
        'status', 'approved_by', 'approved_at', 'rejection_reason', 'metadata'
    ];

    protected $casts = [
        'status' => LeaveStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Implement LeaveInterface methods...
}
```

### Step 4: Create Repository Implementations

**EloquentEmployeeRepository**

```php
<?php

namespace App\Repositories\Hrm;

use App\Models\Hrm\Employee;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\Contracts\EmployeeRepositoryInterface;
use Nexus\Hrm\Exceptions\EmployeeNotFoundException;
use Nexus\Hrm\Exceptions\EmployeeDuplicateException;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Symfony\Component\Uid\Ulid;

final readonly class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    public function findById(string $id): ?EmployeeInterface
    {
        return Employee::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->find($id);
    }

    public function findByEmployeeCode(string $tenantId, string $employeeCode): ?EmployeeInterface
    {
        return Employee::where('tenant_id', $tenantId)
            ->where('employee_code', $employeeCode)
            ->first();
    }

    public function findByEmail(string $tenantId, string $email): ?EmployeeInterface
    {
        return Employee::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();
    }

    public function getAll(string $tenantId, array $filters = []): array
    {
        $query = Employee::where('tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        return $query->get()->all();
    }

    public function save(EmployeeInterface $employee): void
    {
        if ($employee instanceof Employee) {
            // Check for duplicates
            $this->checkDuplicateCode($employee);
            $this->checkDuplicateEmail($employee);

            $employee->save();
        }
    }

    public function delete(string $id): void
    {
        Employee::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('id', $id)
            ->delete();
    }

    private function checkDuplicateCode(Employee $employee): void
    {
        $exists = Employee::where('tenant_id', $employee->tenant_id)
            ->where('employee_code', $employee->employee_code)
            ->where('id', '!=', $employee->id)
            ->exists();

        if ($exists) {
            throw EmployeeDuplicateException::withCode($employee->employee_code);
        }
    }

    private function checkDuplicateEmail(Employee $employee): void
    {
        $exists = Employee::where('tenant_id', $employee->tenant_id)
            ->where('email', $employee->email)
            ->where('id', '!=', $employee->id)
            ->exists();

        if ($exists) {
            throw EmployeeDuplicateException::withEmail($employee->email);
        }
    }
}
```

### Step 5: Create Service Provider

**HrmServiceProvider**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Hrm\Contracts\EmployeeRepositoryInterface;
use Nexus\Hrm\Contracts\LeaveRepositoryInterface;
use Nexus\Hrm\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Hrm\Contracts\AttendanceRepositoryInterface;
use Nexus\Hrm\Contracts\PerformanceReviewRepositoryInterface;
use Nexus\Hrm\Contracts\DisciplinaryRepositoryInterface;
use Nexus\Hrm\Contracts\TrainingRepositoryInterface;
use Nexus\Hrm\Contracts\TrainingEnrollmentRepositoryInterface;
use App\Repositories\Hrm\EloquentEmployeeRepository;
use App\Repositories\Hrm\EloquentLeaveRepository;
use App\Repositories\Hrm\EloquentLeaveBalanceRepository;
use App\Repositories\Hrm\EloquentAttendanceRepository;
use App\Repositories\Hrm\EloquentPerformanceReviewRepository;
use App\Repositories\Hrm\EloquentDisciplinaryRepository;
use App\Repositories\Hrm\EloquentTrainingRepository;
use App\Repositories\Hrm\EloquentTrainingEnrollmentRepository;

class HrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind all repository interfaces
        $this->app->singleton(
            EmployeeRepositoryInterface::class,
            EloquentEmployeeRepository::class
        );

        $this->app->singleton(
            LeaveRepositoryInterface::class,
            EloquentLeaveRepository::class
        );

        $this->app->singleton(
            LeaveBalanceRepositoryInterface::class,
            EloquentLeaveBalanceRepository::class
        );

        $this->app->singleton(
            AttendanceRepositoryInterface::class,
            EloquentAttendanceRepository::class
        );

        $this->app->singleton(
            PerformanceReviewRepositoryInterface::class,
            EloquentPerformanceReviewRepository::class
        );

        $this->app->singleton(
            DisciplinaryRepositoryInterface::class,
            EloquentDisciplinaryRepository::class
        );

        $this->app->singleton(
            TrainingRepositoryInterface::class,
            EloquentTrainingRepository::class
        );

        $this->app->singleton(
            TrainingEnrollmentRepositoryInterface::class,
            EloquentTrainingEnrollmentRepository::class
        );

        // Managers auto-resolve via constructor injection
    }
}
```

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\HrmServiceProvider::class,
],
```

### Step 6: Use in Controllers

**EmployeeController**

```php
<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nexus\Hrm\Services\EmployeeManager;
use Nexus\Hrm\ValueObjects\EmploymentType;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeManager $employeeManager
    ) {}

    public function index(Request $request)
    {
        $employees = $this->employeeManager->getAllEmployees([
            'status' => $request->get('status'),
            'department_id' => $request->get('department_id'),
        ]);

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone_number' => 'nullable|string|max:50',
            'date_of_birth' => 'required|date',
            'hire_date' => 'required|date',
            'employment_type' => 'required|string',
            'job_title' => 'nullable|string',
        ]);

        $employee = $this->employeeManager->createEmployee([
            'employee_code' => $validated['employee_code'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'hire_date' => $validated['hire_date'],
            'employment_type' => EmploymentType::from($validated['employment_type']),
            'job_title' => $validated['job_title'] ?? null,
        ]);

        return response()->json($employee, 201);
    }

    public function confirm(Request $request, string $id)
    {
        $validated = $request->validate([
            'confirmation_date' => 'required|date',
        ]);

        $employee = $this->employeeManager->confirmEmployee(
            $id,
            new \DateTimeImmutable($validated['confirmation_date'])
        );

        return response()->json($employee);
    }
}
```

**LeaveController**

```php
<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nexus\Hrm\Services\LeaveManager;

class LeaveController extends Controller
{
    public function __construct(
        private readonly LeaveManager $leaveManager
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'leave_type_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $leaveRequest = $this->leaveManager->createLeaveRequest($validated);

        return response()->json($leaveRequest, 201);
    }

    public function approve(string $id)
    {
        $approverId = auth()->user()->employee_id;

        $leave = $this->leaveManager->approveLeaveRequest($id, $approverId);

        return response()->json($leave);
    }

    public function reject(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $leave = $this->leaveManager->rejectLeaveRequest($id, $validated['reason']);

        return response()->json($leave);
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/hrm:"*@dev"
```

### Step 2: Create Doctrine Entities

**Employee Entity**

```php
<?php

namespace App\Entity\Hrm;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\ValueObjects\EmployeeStatus;
use Nexus\Hrm\ValueObjects\EmploymentType;

#[ORM\Entity]
#[ORM\Table(name: 'employees')]
class Employee implements EmployeeInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $employeeCode;

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $dateOfBirth;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $hireDate;

    #[ORM\Column(type: 'string', length: 50, enumType: EmployeeStatus::class)]
    private EmployeeStatus $status;

    #[ORM\Column(type: 'string', length: 50, enumType: EmploymentType::class)]
    private EmploymentType $employmentType;

    // Implement EmployeeInterface methods...
}
```

### Step 3: Create Repository

**EmployeeRepository**

```php
<?php

namespace App\Repository\Hrm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\Contracts\EmployeeRepositoryInterface;
use App\Entity\Hrm\Employee;

class EmployeeRepository extends ServiceEntityRepository implements EmployeeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function findById(string $id): ?EmployeeInterface
    {
        return $this->find($id);
    }

    // Implement other repository methods...
}
```

### Step 4: Configure Services

**config/services.yaml**

```yaml
services:
    # Repositories
    Nexus\Hrm\Contracts\EmployeeRepositoryInterface:
        class: App\Repository\Hrm\EmployeeRepository

    Nexus\Hrm\Contracts\LeaveRepositoryInterface:
        class: App\Repository\Hrm\LeaveRepository

    Nexus\Hrm\Contracts\AttendanceRepositoryInterface:
        class: App\Repository\Hrm\AttendanceRepository

    # Managers (auto-wired)
    Nexus\Hrm\Services\EmployeeManager: ~
    Nexus\Hrm\Services\LeaveManager: ~
    Nexus\Hrm\Services\AttendanceManager: ~
```

### Step 5: Use in Controller

```php
<?php

namespace App\Controller\Hrm;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nexus\Hrm\Services\EmployeeManager;

class EmployeeController extends AbstractController
{
    public function __construct(
        private readonly EmployeeManager $employeeManager
    ) {}

    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $employee = $this->employeeManager->createEmployee($data);

        return $this->json($employee, 201);
    }
}
```

---

## Common Patterns

### Pattern 1: Employee Onboarding Flow

```php
// 1. Create employee
$employee = $employeeManager->createEmployee([...]);

// 2. Create contract
$contract = $contractManager->createContract([
    'employee_id' => $employee->getId(),
    'start_date' => '2025-01-01',
    'probation_months' => 3,
]);

// 3. Setup leave entitlements
$leaveManager->setupLeaveEntitlements($employee->getId(), [
    'annual_leave' => 14,
    'medical_leave' => 14,
]);

// 4. Assign to department (via Backoffice integration)
$backofficeManager->assignEmployee($employee->getId(), $departmentId);
```

### Pattern 2: Leave Approval Workflow

```php
// Employee creates request
$leave = $leaveManager->createLeaveRequest([...]);

// Trigger workflow (Nexus\Workflow integration)
$workflowManager->startProcess('leave_approval', [
    'leave_id' => $leave->getId(),
    'employee_id' => $leave->getEmployeeId(),
    'approver_id' => $employee->getManagerId(),
]);

// Manager approves via workflow callback
$leaveManager->approveLeaveRequest($leave->getId(), $managerId);
```

### Pattern 3: Attendance with GPS Validation

```php
// Clock in with geofencing validation
$latitude = $request->get('latitude');
$longitude = $request->get('longitude');

// Validate within office radius (custom logic)
if (!$this->isWithinOfficeRadius($latitude, $longitude)) {
    return response()->json(['error' => 'Outside office area'], 422);
}

$attendance = $attendanceManager->clockIn($employeeId, [
    'location' => 'Office HQ',
    'latitude' => $latitude,
    'longitude' => $longitude,
]);
```

---

## Troubleshooting

### Issue: Interface not bound

**Solution:** Ensure service provider is registered and all interfaces are bound to implementations.

### Issue: Multi-tenancy not working

**Solution:** Ensure `Nexus\Tenant` package is installed and tenant middleware is active. Repository queries must use `TenantContextInterface`.

### Issue: Leave balance calculation errors

**Solution:** Verify leave types are configured with correct entitlement values. Check accrual policy settings.

---

## Performance Optimization

### Database Indexes

```sql
-- Critical indexes
CREATE INDEX idx_employees_tenant_status ON employees(tenant_id, status);
CREATE INDEX idx_leave_requests_employee_status ON leave_requests(tenant_id, employee_id, status);
CREATE INDEX idx_attendance_employee_date ON attendance_records(tenant_id, employee_id, clock_in_time);
```

### Caching Strategy

```php
// Cache leave balances
Cache::remember("leave_balance.{$employeeId}.{$leaveTypeId}", 3600, fn() => 
    $leaveManager->getLeaveBalance($employeeId, $leaveTypeId)
);

// Invalidate on leave approval
Cache::forget("leave_balance.{$employeeId}.{$leaveTypeId}");
```

---

**Integration Guide Version:** 1.0.0  
**Last Updated:** 2025-11-25
