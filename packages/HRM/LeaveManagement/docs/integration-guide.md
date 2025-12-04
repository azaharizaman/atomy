# Integration Guide: LeaveManagement

This guide demonstrates how to integrate the `Nexus\LeaveManagement` package into Laravel and Symfony applications.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/leave-management:"*@dev"
```

### Step 2: Create Migration

Create a migration for leave tables:

```bash
php artisan make:migration create_leave_management_tables
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leave types table
        Schema::create('leave_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('name');
            $table->string('category'); // annual, sick, maternity, etc.
            $table->decimal('annual_entitlement', 5, 2)->nullable();
            $table->string('accrual_frequency'); // monthly, yearly, fixed_allocation
            $table->decimal('max_carry_forward', 5, 2)->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->timestamps();
        });

        // Leave balances table
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('employee_id')->index();
            $table->ulid('leave_type_id');
            $table->decimal('balance', 8, 2)->default(0);
            $table->decimal('accrued_ytd', 8, 2)->default(0);
            $table->decimal('used_ytd', 8, 2)->default(0);
            $table->integer('year');
            $table->timestamps();

            $table->foreign('leave_type_id')
                ->references('id')
                ->on('leave_types')
                ->onDelete('restrict');

            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        // Leave requests table
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('employee_id')->index();
            $table->ulid('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 2);
            $table->string('duration_type')->default('full_day');
            $table->string('status')->default('draft');
            $table->text('reason')->nullable();
            $table->ulid('approver_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('leave_type_id')
                ->references('id')
                ->on('leave_types')
                ->onDelete('restrict');

            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
```

### Step 3: Create Eloquent Models

**LeaveType Model:**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'annual_entitlement',
        'accrual_frequency',
        'max_carry_forward',
        'is_paid',
        'requires_approval',
    ];

    protected $casts = [
        'annual_entitlement' => 'float',
        'max_carry_forward' => 'float',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
```

**LeaveBalance Model:**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'balance',
        'accrued_ytd',
        'used_ytd',
        'year',
    ];

    protected $casts = [
        'balance' => 'float',
        'accrued_ytd' => 'float',
        'used_ytd' => 'float',
        'year' => 'integer',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
```

**LeaveRequest Model:**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'duration_type',
        'status',
        'reason',
        'approver_id',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'float',
        'approved_at' => 'datetime',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
```

### Step 4: Implement Repository Interfaces

**LeaveRepository:**

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Leave;

use App\Models\LeaveRequest;
use Nexus\LeaveManagement\Contracts\LeaveRepositoryInterface;

final readonly class EloquentLeaveRepository implements LeaveRepositoryInterface
{
    public function __construct(
        private LeaveRequest $model
    ) {}

    public function findById(string $id): ?object
    {
        return $this->model->find($id);
    }

    public function findByEmployeeId(string $employeeId): array
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->orderBy('start_date', 'desc')
            ->get()
            ->all();
    }

    public function save(object $leave): string
    {
        $leave->save();
        return $leave->id;
    }

    public function delete(string $id): void
    {
        $this->model->findOrFail($id)->delete();
    }
}
```

**LeaveBalanceRepository:**

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Leave;

use App\Models\LeaveBalance;
use Nexus\LeaveManagement\Contracts\LeaveBalanceRepositoryInterface;

final readonly class EloquentLeaveBalanceRepository implements LeaveBalanceRepositoryInterface
{
    public function __construct(
        private LeaveBalance $model
    ) {}

    public function findByEmployeeAndType(string $employeeId, string $leaveTypeId): ?object
    {
        $currentYear = now()->year;

        return $this->model
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $currentYear)
            ->first();
    }

    public function save(object $balance): string
    {
        $balance->save();
        return $balance->id;
    }

    public function updateBalance(string $balanceId, float $amount): void
    {
        $this->model->findOrFail($balanceId)->update([
            'balance' => $amount,
        ]);
    }
}
```

**LeaveTypeRepository:**

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Leave;

use App\Models\LeaveType;
use Nexus\LeaveManagement\Contracts\LeaveTypeRepositoryInterface;

final readonly class EloquentLeaveTypeRepository implements LeaveTypeRepositoryInterface
{
    public function __construct(
        private LeaveType $model
    ) {}

    public function findById(string $id): ?object
    {
        return $this->model->find($id);
    }

    public function findAll(): array
    {
        return $this->model->get()->all();
    }

    public function save(object $leaveType): string
    {
        $leaveType->save();
        return $leaveType->id;
    }
}
```

### Step 5: Create Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\LeaveManagement\Contracts\LeaveRepositoryInterface;
use Nexus\LeaveManagement\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\LeaveManagement\Contracts\LeaveTypeRepositoryInterface;
use Nexus\LeaveManagement\Contracts\LeaveCalculatorInterface;
use Nexus\LeaveManagement\Services\LeaveBalanceCalculator;
use App\Repositories\Leave\EloquentLeaveRepository;
use App\Repositories\Leave\EloquentLeaveBalanceRepository;
use App\Repositories\Leave\EloquentLeaveTypeRepository;

class LeaveManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            LeaveRepositoryInterface::class,
            EloquentLeaveRepository::class
        );

        $this->app->singleton(
            LeaveBalanceRepositoryInterface::class,
            EloquentLeaveBalanceRepository::class
        );

        $this->app->singleton(
            LeaveTypeRepositoryInterface::class,
            EloquentLeaveTypeRepository::class
        );

        // Bind calculator service
        $this->app->singleton(
            LeaveCalculatorInterface::class,
            LeaveBalanceCalculator::class
        );
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
```

Register the provider in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\LeaveManagementServiceProvider::class,
],
```

### Step 6: Create Application Service

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LeaveRequest;
use Nexus\LeaveManagement\Contracts\LeaveRepositoryInterface;
use Nexus\LeaveManagement\Contracts\LeaveCalculatorInterface;
use Nexus\LeaveManagement\Enums\LeaveStatus;
use Nexus\LeaveManagement\Exceptions\InsufficientBalanceException;

final readonly class LeaveApplicationService
{
    public function __construct(
        private LeaveRepositoryInterface $repository,
        private LeaveCalculatorInterface $calculator
    ) {}

    public function apply(
        string $employeeId,
        string $leaveTypeId,
        string $startDate,
        string $endDate,
        float $days,
        string $reason
    ): string {
        // Check balance
        $balance = $this->calculator->calculateBalance($employeeId, $leaveTypeId);

        if ($days > $balance) {
            throw new InsufficientBalanceException(
                $employeeId,
                $leaveTypeId,
                $days,
                $balance
            );
        }

        // Create leave request
        $leave = new LeaveRequest([
            'tenant_id' => auth()->user()->tenant_id,
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'status' => LeaveStatus::PENDING->value,
            'reason' => $reason,
        ]);

        return $this->repository->save($leave);
    }

    public function getEmployeeLeaves(string $employeeId): array
    {
        return $this->repository->findByEmployeeId($employeeId);
    }
}
```

### Step 7: Create Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LeaveApplicationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\LeaveManagement\Exceptions\InsufficientBalanceException;

class LeaveController extends Controller
{
    public function __construct(
        private readonly LeaveApplicationService $leaveService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'leave_type_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'required|numeric|min:0.5',
            'reason' => 'nullable|string',
        ]);

        try {
            $leaveId = $this->leaveService->apply(
                $validated['employee_id'],
                $validated['leave_type_id'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['days'],
                $validated['reason'] ?? ''
            );

            return response()->json([
                'message' => 'Leave application submitted successfully',
                'leave_id' => $leaveId,
            ], 201);

        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $employeeId = $request->input('employee_id') ?? auth()->user()->employee_id;

        $leaves = $this->leaveService->getEmployeeLeaves($employeeId);

        return response()->json([
            'data' => $leaves,
        ]);
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/leave-management:"*@dev"
```

### Step 2: Create Doctrine Entities

**LeaveType Entity:**

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'leave_types')]
class LeaveType
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'string')]
    private string $category;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?float $annualEntitlement = null;

    #[ORM\Column(type: 'string')]
    private string $accrualFrequency;

    #[ORM\Column(type: 'boolean')]
    private bool $isPaid = true;

    // Getters and setters...
}
```

**LeaveBalance Entity:**

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'leave_balances')]
#[ORM\UniqueConstraint(columns: ['employee_id', 'leave_type_id', 'year'])]
class LeaveBalance
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string')]
    private string $employeeId;

    #[ORM\ManyToOne(targetEntity: LeaveType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private LeaveType $leaveType;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private float $balance = 0.0;

    #[ORM\Column(type: 'integer')]
    private int $year;

    // Getters and setters...
}
```

### Step 3: Implement Repositories

**DoctrineLeaveRepository:**

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LeaveRequest;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\LeaveManagement\Contracts\LeaveRepositoryInterface;

final readonly class DoctrineLeaveRepository implements LeaveRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(string $id): ?object
    {
        return $this->entityManager->find(LeaveRequest::class, $id);
    }

    public function findByEmployeeId(string $employeeId): array
    {
        return $this->entityManager
            ->getRepository(LeaveRequest::class)
            ->findBy(['employeeId' => $employeeId], ['startDate' => 'DESC']);
    }

    public function save(object $leave): string
    {
        $this->entityManager->persist($leave);
        $this->entityManager->flush();
        return $leave->getId();
    }

    public function delete(string $id): void
    {
        $leave = $this->findById($id);
        if ($leave) {
            $this->entityManager->remove($leave);
            $this->entityManager->flush();
        }
    }
}
```

### Step 4: Configure Services

**config/services.yaml:**

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Repositories
    Nexus\LeaveManagement\Contracts\LeaveRepositoryInterface:
        class: App\Repository\DoctrineLeaveRepository

    Nexus\LeaveManagement\Contracts\LeaveBalanceRepositoryInterface:
        class: App\Repository\DoctrineLeaveBalanceRepository

    Nexus\LeaveManagement\Contracts\LeaveTypeRepositoryInterface:
        class: App\Repository\DoctrineLeaveTypeRepository

    # Calculator service
    Nexus\LeaveManagement\Contracts\LeaveCalculatorInterface:
        class: Nexus\LeaveManagement\Services\LeaveBalanceCalculator
        arguments:
            $balanceRepository: '@Nexus\LeaveManagement\Contracts\LeaveBalanceRepositoryInterface'
```

### Step 5: Create Application Service

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\LeaveRequest;
use Nexus\LeaveManagement\Contracts\LeaveRepositoryInterface;
use Nexus\LeaveManagement\Contracts\LeaveCalculatorInterface;
use Nexus\LeaveManagement\Enums\LeaveStatus;
use Nexus\LeaveManagement\Exceptions\InsufficientBalanceException;

final readonly class LeaveApplicationService
{
    public function __construct(
        private LeaveRepositoryInterface $repository,
        private LeaveCalculatorInterface $calculator
    ) {}

    public function apply(
        string $employeeId,
        string $leaveTypeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        float $days,
        string $reason
    ): string {
        // Check balance
        $balance = $this->calculator->calculateBalance($employeeId, $leaveTypeId);

        if ($days > $balance) {
            throw new InsufficientBalanceException(
                $employeeId,
                $leaveTypeId,
                $days,
                $balance
            );
        }

        // Create leave request
        $leave = new LeaveRequest();
        $leave->setEmployeeId($employeeId);
        $leave->setLeaveTypeId($leaveTypeId);
        $leave->setStartDate($startDate);
        $leave->setEndDate($endDate);
        $leave->setDays($days);
        $leave->setStatus(LeaveStatus::PENDING->value);
        $leave->setReason($reason);

        return $this->repository->save($leave);
    }
}
```

### Step 6: Create Controller

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LeaveApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\LeaveManagement\Exceptions\InsufficientBalanceException;

#[Route('/api/leave')]
class LeaveController extends AbstractController
{
    public function __construct(
        private readonly LeaveApplicationService $leaveService
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $leaveId = $this->leaveService->apply(
                $data['employee_id'],
                $data['leave_type_id'],
                new \DateTimeImmutable($data['start_date']),
                new \DateTimeImmutable($data['end_date']),
                (float) $data['days'],
                $data['reason'] ?? ''
            );

            return $this->json([
                'message' => 'Leave application submitted',
                'leave_id' => $leaveId,
            ], 201);

        } catch (InsufficientBalanceException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
```

---

## Testing Your Integration

### Laravel PHPUnit Test

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Nexus\LeaveManagement\Enums\LeaveStatus;

class LeaveApplicationTest extends TestCase
{
    public function test_can_apply_for_leave_with_sufficient_balance(): void
    {
        // Arrange
        $employee = User::factory()->create();
        $leaveType = LeaveType::factory()->create();
        LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'balance' => 10.0,
        ]);

        // Act
        $response = $this->postJson('/api/leave', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-05',
            'days' => 5,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'status' => LeaveStatus::PENDING->value,
        ]);
    }

    public function test_cannot_apply_with_insufficient_balance(): void
    {
        // Arrange
        $employee = User::factory()->create();
        $leaveType = LeaveType::factory()->create();
        LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'balance' => 3.0,
        ]);

        // Act
        $response = $this->postJson('/api/leave', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-05',
            'days' => 5,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Insufficient balance']);
    }
}
```

---

## Best Practices

1. **Always Check Balance Before Submission**
   ```php
   $balance = $calculator->calculateBalance($employeeId, $leaveTypeId);
   // Display available balance to user
   ```

2. **Use Transactions for Leave Application**
   ```php
   DB::transaction(function () use ($leaveData) {
       $this->leaveService->apply(...$leaveData);
       $this->notificationService->notifyManager($leaveData['approver_id']);
   });
   ```

3. **Implement Policy Validation**
   ```php
   if (!$policy->canApplyLeave($employeeId, $leaveTypeId, $start, $end)) {
       throw new PolicyViolationException();
   }
   ```

4. **Log All Leave Actions**
   ```php
   $this->auditLogger->log(
       $leaveId,
       'leave_applied',
       "Employee {$employeeId} applied for {$days} days leave"
   );
   ```

## Next Steps

- Implement leave approval workflows in your orchestrator layer
- Add notifications for leave status changes
- Create UI components for leave management
- Set up scheduled jobs for accrual processing
