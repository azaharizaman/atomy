# Integration Guide: Nexus Attendance

**Package:** `nexus/attendance-management`  
**Target Frameworks:** Laravel 10+, Symfony 6+

This guide provides detailed instructions for integrating the Attendance package into Laravel and Symfony applications.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Testing Integration](#testing-integration)
4. [Deployment Checklist](#deployment-checklist)

---

## Laravel Integration

### Step 1: Install Package and Adapter

```bash
composer require nexus/attendance-management
```

For Laravel-specific adapter (optional):
```bash
composer require nexus/attendance-laravel-adapter
```

### Step 2: Create Database Migrations

```bash
php artisan make:migration create_attendance_tables
```

**Migration File:** `database/migrations/xxxx_xx_xx_create_attendance_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('employee_id')->index();
            $table->string('tenant_id')->index();
            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time')->nullable();
            $table->json('check_in_coordinates')->nullable();
            $table->json('check_out_coordinates')->nullable();
            $table->string('status', 50)->index(); // 'checked_in', 'checked_out'
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Composite indexes for common queries
            $table->index(['employee_id', 'check_in_time']);
            $table->index(['tenant_id', 'check_in_time']);
            $table->index(['status', 'employee_id']);
        });

        Schema::create('work_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->json('days_of_week'); // [1,2,3,4,5] (Monday-Friday)
            $table->unsignedInteger('grace_minutes')->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'effective_from', 'effective_to']);
        });

        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();
            $table->ulid('schedule_id');
            $table->string('tenant_id')->index();
            $table->date('assigned_from');
            $table->date('assigned_to')->nullable();
            $table->timestamps();
            
            $table->foreign('schedule_id')
                ->references('id')
                ->on('work_schedules')
                ->onDelete('cascade');
            
            $table->index(['employee_id', 'assigned_from', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('work_schedules');
        Schema::dropIfExists('attendance_records');
    }
};
```

Run migration:
```bash
php artisan migrate
```

### Step 3: Create Eloquent Models

**File:** `app/Models/AttendanceRecord.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_id',
        'tenant_id',
        'check_in_time',
        'check_out_time',
        'check_in_coordinates',
        'check_out_coordinates',
        'status',
        'notes',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in_coordinates' => 'array',
        'check_out_coordinates' => 'array',
    ];

    // Scopes
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeBetweenDates($query, \DateTime $start, \DateTime $end)
    {
        return $query->whereBetween('check_in_time', [$start, $end]);
    }
}
```

**File:** `app/Models/WorkSchedule.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSchedule extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'work_schedules';

    protected $fillable = [
        'tenant_id',
        'name',
        'start_time',
        'end_time',
        'days_of_week',
        'grace_minutes',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'days_of_week' => 'array',
        'grace_minutes' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeEffectiveOn($query, \DateTime $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }
}
```

### Step 4: Create Repository Implementations

**File:** `app/Repositories/Attendance/EloquentAttendanceQueryRepository.php`

```php
<?php

namespace App\Repositories\Attendance;

use App\Models\AttendanceRecord as EloquentAttendanceRecord;
use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\AttendanceRecordInterface;
use Nexus\Attendance\Domain\Entities\AttendanceRecord;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;

final readonly class EloquentAttendanceQueryRepository implements AttendanceQueryInterface
{
    public function findById(AttendanceId $id): ?AttendanceRecordInterface
    {
        $model = EloquentAttendanceRecord::find($id->toString());
        
        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmployee(string $employeeId): array
    {
        $models = EloquentAttendanceRecord::forEmployee($employeeId)
            ->orderBy('check_in_time', 'desc')
            ->get();
        
        return $models->map(fn($m) => $this->toDomain($m))->all();
    }

    public function findByEmployeeAndDateRange(
        string $employeeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $models = EloquentAttendanceRecord::forEmployee($employeeId)
            ->betweenDates($startDate, $endDate)
            ->orderBy('check_in_time', 'desc')
            ->get();
        
        return $models->map(fn($m) => $this->toDomain($m))->all();
    }

    public function findOpenRecordByEmployee(string $employeeId): ?AttendanceRecordInterface
    {
        $model = EloquentAttendanceRecord::forEmployee($employeeId)
            ->open()
            ->first();
        
        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentAttendanceRecord $model): AttendanceRecord
    {
        return new AttendanceRecord(
            id: new AttendanceId($model->id),
            employeeId: $model->employee_id,
            tenantId: $model->tenant_id,
            checkInTime: \DateTimeImmutable::createFromMutable($model->check_in_time),
            checkOutTime: $model->check_out_time
                ? \DateTimeImmutable::createFromMutable($model->check_out_time)
                : null,
            checkInCoordinates: $model->check_in_coordinates,
            checkOutCoordinates: $model->check_out_coordinates,
            notes: $model->notes
        );
    }
}
```

**File:** `app/Repositories/Attendance/EloquentAttendancePersistRepository.php`

```php
<?php

namespace App\Repositories\Attendance;

use App\Models\AttendanceRecord as EloquentAttendanceRecord;
use Nexus\Attendance\Contracts\AttendancePersistInterface;
use Nexus\Attendance\Contracts\AttendanceRecordInterface;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;
use Nexus\Attendance\Exceptions\AttendanceNotFoundException;

final readonly class EloquentAttendancePersistRepository implements AttendancePersistInterface
{
    public function save(AttendanceRecordInterface $record): AttendanceId
    {
        $model = EloquentAttendanceRecord::updateOrCreate(
            ['id' => $record->getId()->toString()],
            [
                'employee_id' => $record->getEmployeeId(),
                'tenant_id' => $record->getTenantId(),
                'check_in_time' => $record->getCheckInTime(),
                'check_out_time' => $record->getCheckOutTime(),
                'check_in_coordinates' => $record->getCheckInCoordinates(),
                'check_out_coordinates' => $record->getCheckOutCoordinates(),
                'status' => $record->getStatus()->value,
                'notes' => $record->getNotes(),
            ]
        );
        
        return new AttendanceId($model->id);
    }

    public function delete(AttendanceId $id): void
    {
        $deleted = EloquentAttendanceRecord::where('id', $id->toString())->delete();
        
        if (!$deleted) {
            throw AttendanceNotFoundException::withId($id);
        }
    }
}
```

**File:** `app/Repositories/Attendance/EloquentWorkScheduleQueryRepository.php`

```php
<?php

namespace App\Repositories\Attendance;

use App\Models\WorkSchedule as EloquentWorkSchedule;
use App\Models\EmployeeSchedule;
use Nexus\Attendance\Contracts\WorkScheduleQueryInterface;
use Nexus\Attendance\Contracts\WorkScheduleInterface;
use Nexus\Attendance\Domain\Entities\WorkSchedule;
use Nexus\Attendance\Domain\ValueObjects\ScheduleId;

final readonly class EloquentWorkScheduleQueryRepository implements WorkScheduleQueryInterface
{
    public function findById(ScheduleId $id): ?WorkScheduleInterface
    {
        $model = EloquentWorkSchedule::find($id->toString());
        
        return $model ? $this->toDomain($model) : null;
    }

    public function findByTenant(string $tenantId): array
    {
        $models = EloquentWorkSchedule::forTenant($tenantId)
            ->orderBy('name')
            ->get();
        
        return $models->map(fn($m) => $this->toDomain($m))->all();
    }

    public function findEffectiveSchedule(string $employeeId, \DateTimeImmutable $date): ?WorkScheduleInterface
    {
        // Find employee's assigned schedule
        $assignment = EmployeeSchedule::where('employee_id', $employeeId)
            ->where('assigned_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('assigned_to')
                    ->orWhere('assigned_to', '>=', $date);
            })
            ->first();
        
        if (!$assignment) {
            return null;
        }
        
        $model = EloquentWorkSchedule::find($assignment->schedule_id);
        
        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentWorkSchedule $model): WorkSchedule
    {
        return new WorkSchedule(
            id: new ScheduleId($model->id),
            tenantId: $model->tenant_id,
            name: $model->name,
            startTime: \DateTimeImmutable::createFromMutable($model->start_time),
            endTime: \DateTimeImmutable::createFromMutable($model->end_time),
            daysOfWeek: $model->days_of_week,
            graceMinutes: $model->grace_minutes,
            effectiveFrom: \DateTimeImmutable::createFromMutable($model->effective_from),
            effectiveTo: $model->effective_to
                ? \DateTimeImmutable::createFromMutable($model->effective_to)
                : null
        );
    }
}
```

**File:** `app/Repositories/Attendance/EloquentWorkSchedulePersistRepository.php`

```php
<?php

namespace App\Repositories\Attendance;

use App\Models\WorkSchedule as EloquentWorkSchedule;
use Nexus\Attendance\Contracts\WorkSchedulePersistInterface;
use Nexus\Attendance\Contracts\WorkScheduleInterface;
use Nexus\Attendance\Domain\ValueObjects\ScheduleId;
use Nexus\Attendance\Exceptions\WorkScheduleNotFoundException;

final readonly class EloquentWorkSchedulePersistRepository implements WorkSchedulePersistInterface
{
    public function save(WorkScheduleInterface $schedule): ScheduleId
    {
        $model = EloquentWorkSchedule::updateOrCreate(
            ['id' => $schedule->getId()->toString()],
            [
                'tenant_id' => $schedule->getTenantId(),
                'name' => $schedule->getName(),
                'start_time' => $schedule->getStartTime(),
                'end_time' => $schedule->getEndTime(),
                'days_of_week' => $schedule->getDaysOfWeek(),
                'grace_minutes' => $schedule->getGraceMinutes(),
                'effective_from' => $schedule->getEffectiveFrom(),
                'effective_to' => $schedule->getEffectiveTo(),
            ]
        );
        
        return new ScheduleId($model->id);
    }

    public function delete(ScheduleId $id): void
    {
        $deleted = EloquentWorkSchedule::where('id', $id->toString())->delete();
        
        if (!$deleted) {
            throw WorkScheduleNotFoundException::withId($id);
        }
    }
}
```

### Step 5: Register Services in Container

**File:** `app/Providers/AttendanceServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\AttendancePersistInterface;
use Nexus\Attendance\Contracts\WorkScheduleQueryInterface;
use Nexus\Attendance\Contracts\WorkSchedulePersistInterface;
use Nexus\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\Attendance\Contracts\WorkScheduleResolverInterface;
use Nexus\Attendance\Contracts\OvertimeCalculatorInterface;
use App\Repositories\Attendance\EloquentAttendanceQueryRepository;
use App\Repositories\Attendance\EloquentAttendancePersistRepository;
use App\Repositories\Attendance\EloquentWorkScheduleQueryRepository;
use App\Repositories\Attendance\EloquentWorkSchedulePersistRepository;
use Nexus\Attendance\Services\AttendanceManager;
use Nexus\Attendance\Services\WorkScheduleResolver;
use Nexus\Attendance\Services\OvertimeCalculator;
use Psr\Log\LoggerInterface;

class AttendanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind CQRS repository interfaces
        $this->app->singleton(
            AttendanceQueryInterface::class,
            EloquentAttendanceQueryRepository::class
        );

        $this->app->singleton(
            AttendancePersistInterface::class,
            EloquentAttendancePersistRepository::class
        );

        $this->app->singleton(
            WorkScheduleQueryInterface::class,
            EloquentWorkScheduleQueryRepository::class
        );

        $this->app->singleton(
            WorkSchedulePersistInterface::class,
            EloquentWorkSchedulePersistRepository::class
        );

        // Bind domain services
        $this->app->singleton(WorkScheduleResolverInterface::class, function ($app) {
            return new WorkScheduleResolver(
                $app->make(WorkScheduleQueryInterface::class)
            );
        });

        $this->app->singleton(OvertimeCalculatorInterface::class, OvertimeCalculator::class);

        $this->app->singleton(AttendanceManagerInterface::class, function ($app) {
            return new AttendanceManager(
                $app->make(AttendanceQueryInterface::class),
                $app->make(AttendancePersistInterface::class),
                $app->make(LoggerInterface::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
```

Register provider in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\AttendanceServiceProvider::class,
],
```

### Step 6: Create API Controllers

**File:** `app/Http/Controllers/Api/AttendanceController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;
use Nexus\Attendance\Exceptions\AlreadyCheckedInException;
use Nexus\Attendance\Exceptions\AttendanceNotFoundException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceManagerInterface $attendanceManager
    ) {}

    /**
     * POST /api/attendance/check-in
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'tenant_id' => 'required|string',
            'coordinates' => 'nullable|array',
            'coordinates.lat' => 'required_with:coordinates|numeric',
            'coordinates.lng' => 'required_with:coordinates|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $attendanceId = $this->attendanceManager->checkIn(
                employeeId: $validated['employee_id'],
                tenantId: $validated['tenant_id'],
                checkInTime: new \DateTimeImmutable(),
                coordinates: $validated['coordinates'] ?? null,
                notes: $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'attendance_id' => $attendanceId->toString(),
                    'check_in_time' => now()->toIso8601String(),
                ],
            ], 201);

        } catch (AlreadyCheckedInException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * POST /api/attendance/{id}/check-out
     */
    public function checkOut(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'coordinates' => 'nullable|array',
            'coordinates.lat' => 'required_with:coordinates|numeric',
            'coordinates.lng' => 'required_with:coordinates|numeric',
        ]);

        try {
            $this->attendanceManager->checkOut(
                attendanceId: AttendanceId::fromString($id),
                checkOutTime: new \DateTimeImmutable(),
                coordinates: $validated['coordinates'] ?? null
            );

            $record = $this->attendanceManager->getAttendance(
                AttendanceId::fromString($id)
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'attendance_id' => $id,
                    'check_out_time' => $record->getCheckOutTime()?->format('c'),
                    'work_hours' => $record->getWorkHours()?->getHours(),
                ],
            ]);

        } catch (AttendanceNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Attendance record not found',
            ], 404);
        }
    }

    /**
     * GET /api/attendance/{id}
     */
    public function show(string $id): JsonResponse
    {
        try {
            $record = $this->attendanceManager->getAttendance(
                AttendanceId::fromString($id)
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $record->getId()->toString(),
                    'employee_id' => $record->getEmployeeId(),
                    'check_in_time' => $record->getCheckInTime()->format('c'),
                    'check_out_time' => $record->getCheckOutTime()?->format('c'),
                    'work_hours' => $record->getWorkHours()?->getHours(),
                    'status' => $record->getStatus()->value,
                    'notes' => $record->getNotes(),
                ],
            ]);

        } catch (AttendanceNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Attendance record not found',
            ], 404);
        }
    }
}
```

**Routes:** `routes/api.php`

```php
use App\Http\Controllers\Api\AttendanceController;

Route::prefix('attendance')->group(function () {
    Route::post('check-in', [AttendanceController::class, 'checkIn']);
    Route::post('{id}/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('{id}', [AttendanceController::class, 'show']);
});
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/attendance-management
```

### Step 2: Create Doctrine Entities

**File:** `src/Entity/AttendanceRecord.php`

```php
<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'attendance_records')]
#[ORM\Index(columns: ['employee_id', 'check_in_time'])]
class AttendanceRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $employeeId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $tenantId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $checkInTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $checkOutTime = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $checkInCoordinates = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $checkOutCoordinates = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->id = new Ulid();
        $this->status = 'checked_in';
    }

    // Getters and setters...
}
```

**File:** `src/Entity/WorkSchedule.php`

```php
<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'work_schedules')]
class WorkSchedule
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $tenantId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: Types::JSON)]
    private array $daysOfWeek = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $graceMinutes = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $effectiveFrom;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $effectiveTo = null;

    public function __construct()
    {
        $this->id = new Ulid();
    }

    // Getters and setters...
}
```

### Step 3: Create Repository Implementations

**File:** `src/Repository/Attendance/DoctrineAttendanceQueryRepository.php`

```php
<?php

namespace App\Repository\Attendance;

use App\Entity\AttendanceRecord as DoctrineAttendanceRecord;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\AttendanceRecordInterface;
use Nexus\Attendance\Domain\Entities\AttendanceRecord;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;

final readonly class DoctrineAttendanceQueryRepository implements AttendanceQueryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(AttendanceId $id): ?AttendanceRecordInterface
    {
        $entity = $this->entityManager->find(DoctrineAttendanceRecord::class, $id->toString());
        
        return $entity ? $this->toDomain($entity) : null;
    }

    public function findByEmployee(string $employeeId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $entities = $qb->select('a')
            ->from(DoctrineAttendanceRecord::class, 'a')
            ->where('a.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('a.checkInTime', 'DESC')
            ->getQuery()
            ->getResult();
        
        return array_map(fn($e) => $this->toDomain($e), $entities);
    }

    public function findByEmployeeAndDateRange(
        string $employeeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $qb = $this->entityManager->createQueryBuilder();
        
        $entities = $qb->select('a')
            ->from(DoctrineAttendanceRecord::class, 'a')
            ->where('a.employeeId = :employeeId')
            ->andWhere('a.checkInTime >= :startDate')
            ->andWhere('a.checkInTime <= :endDate')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.checkInTime', 'DESC')
            ->getQuery()
            ->getResult();
        
        return array_map(fn($e) => $this->toDomain($e), $entities);
    }

    public function findOpenRecordByEmployee(string $employeeId): ?AttendanceRecordInterface
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $entity = $qb->select('a')
            ->from(DoctrineAttendanceRecord::class, 'a')
            ->where('a.employeeId = :employeeId')
            ->andWhere('a.status = :status')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('status', 'checked_in')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        return $entity ? $this->toDomain($entity) : null;
    }

    private function toDomain(DoctrineAttendanceRecord $entity): AttendanceRecord
    {
        return new AttendanceRecord(
            id: new AttendanceId($entity->getId()->toString()),
            employeeId: $entity->getEmployeeId(),
            tenantId: $entity->getTenantId(),
            checkInTime: $entity->getCheckInTime(),
            checkOutTime: $entity->getCheckOutTime(),
            checkInCoordinates: $entity->getCheckInCoordinates(),
            checkOutCoordinates: $entity->getCheckOutCoordinates(),
            notes: $entity->getNotes()
        );
    }
}
```

### Step 4: Configure Services

**File:** `config/services.yaml`

```yaml
services:
    # CQRS Repository Implementations
    App\Repository\Attendance\DoctrineAttendanceQueryRepository:
        arguments:
            - '@doctrine.orm.entity_manager'

    App\Repository\Attendance\DoctrineAttendancePersistRepository:
        arguments:
            - '@doctrine.orm.entity_manager'

    App\Repository\Attendance\DoctrineWorkScheduleQueryRepository:
        arguments:
            - '@doctrine.orm.entity_manager'

    App\Repository\Attendance\DoctrineWorkSchedulePersistRepository:
        arguments:
            - '@doctrine.orm.entity_manager'

    # Bind interfaces to implementations
    Nexus\Attendance\Contracts\AttendanceQueryInterface:
        alias: App\Repository\Attendance\DoctrineAttendanceQueryRepository

    Nexus\Attendance\Contracts\AttendancePersistInterface:
        alias: App\Repository\Attendance\DoctrineAttendancePersistRepository

    Nexus\Attendance\Contracts\WorkScheduleQueryInterface:
        alias: App\Repository\Attendance\DoctrineWorkScheduleQueryRepository

    Nexus\Attendance\Contracts\WorkSchedulePersistInterface:
        alias: App\Repository\Attendance\DoctrineWorkSchedulePersistRepository

    # Domain Services
    Nexus\Attendance\Services\WorkScheduleResolver:
        arguments:
            - '@Nexus\Attendance\Contracts\WorkScheduleQueryInterface'

    Nexus\Attendance\Services\OvertimeCalculator: ~

    Nexus\Attendance\Services\AttendanceManager:
        arguments:
            - '@Nexus\Attendance\Contracts\AttendanceQueryInterface'
            - '@Nexus\Attendance\Contracts\AttendancePersistInterface'
            - '@Psr\Log\LoggerInterface'

    # Alias service interfaces
    Nexus\Attendance\Contracts\AttendanceManagerInterface:
        alias: Nexus\Attendance\Services\AttendanceManager

    Nexus\Attendance\Contracts\WorkScheduleResolverInterface:
        alias: Nexus\Attendance\Services\WorkScheduleResolver

    Nexus\Attendance\Contracts\OvertimeCalculatorInterface:
        alias: Nexus\Attendance\Services\OvertimeCalculator
```

### Step 5: Create API Controller

**File:** `src/Controller/Api/AttendanceController.php`

```php
<?php

namespace App\Controller\Api;

use Nexus\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;
use Nexus\Attendance\Exceptions\AlreadyCheckedInException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/attendance')]
class AttendanceController extends AbstractController
{
    public function __construct(
        private readonly AttendanceManagerInterface $attendanceManager
    ) {}

    #[Route('/check-in', methods: ['POST'])]
    public function checkIn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $attendanceId = $this->attendanceManager->checkIn(
                employeeId: $data['employee_id'],
                tenantId: $data['tenant_id'],
                checkInTime: new \DateTimeImmutable(),
                coordinates: $data['coordinates'] ?? null,
                notes: $data['notes'] ?? null
            );

            return $this->json([
                'success' => true,
                'data' => [
                    'attendance_id' => $attendanceId->toString(),
                    'check_in_time' => (new \DateTimeImmutable())->format('c'),
                ],
            ], 201);

        } catch (AlreadyCheckedInException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 409);
        }
    }

    #[Route('/{id}/check-out', methods: ['POST'])]
    public function checkOut(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $this->attendanceManager->checkOut(
                attendanceId: AttendanceId::fromString($id),
                checkOutTime: new \DateTimeImmutable(),
                coordinates: $data['coordinates'] ?? null
            );

            $record = $this->attendanceManager->getAttendance(
                AttendanceId::fromString($id)
            );

            return $this->json([
                'success' => true,
                'data' => [
                    'attendance_id' => $id,
                    'check_out_time' => $record->getCheckOutTime()?->format('c'),
                    'work_hours' => $record->getWorkHours()?->getHours(),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
```

---

## Testing Integration

### Laravel Testing Example

**File:** `tests/Feature/AttendanceTest.php`

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_check_in(): void
    {
        $response = $this->postJson('/api/attendance/check-in', [
            'employee_id' => 'emp-test-001',
            'tenant_id' => 'tenant-test',
            'coordinates' => [
                'lat' => 3.1390,
                'lng' => 101.6869,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => 'emp-test-001',
            'status' => 'checked_in',
        ]);
    }

    public function test_employee_cannot_check_in_twice(): void
    {
        // First check-in
        $this->postJson('/api/attendance/check-in', [
            'employee_id' => 'emp-test-002',
            'tenant_id' => 'tenant-test',
        ]);

        // Second check-in (should fail)
        $response = $this->postJson('/api/attendance/check-in', [
            'employee_id' => 'emp-test-002',
            'tenant_id' => 'tenant-test',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);
    }
}
```

### Symfony Testing Example

**File:** `tests/Controller/AttendanceControllerTest.php`

```php
<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AttendanceControllerTest extends WebTestCase
{
    public function testCheckIn(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/attendance/check-in', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'employee_id' => 'emp-test-001',
            'tenant_id' => 'tenant-test',
            'coordinates' => ['lat' => 3.1390, 'lng' => 101.6869],
        ]));

        $this->assertResponseStatusCodeSame(201);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('attendance_id', $data['data']);
    }
}
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] Run migrations: `php artisan migrate` (Laravel) or `doctrine:migrations:migrate` (Symfony)
- [ ] Seed work schedules if needed
- [ ] Configure logging (PSR-3 logger binding)
- [ ] Set up database indexes for performance
- [ ] Test API endpoints with Postman/Insomnia
- [ ] Run test suite: `php artisan test` or `symfony run bin/phpunit`

### Post-Deployment

- [ ] Monitor error logs for exceptions
- [ ] Verify database queries are optimized (no N+1)
- [ ] Set up alerts for failed check-ins/check-outs
- [ ] Document API endpoints in Swagger/OpenAPI
- [ ] Train users on mobile/web attendance interface

---

## See Also

- **[Getting Started Guide](getting-started.md)** - Quick start and basic concepts
- **[API Reference](api-reference.md)** - Complete API documentation
- **[Examples](examples/)** - Runnable code samples
- **[Package Requirements](../REQUIREMENTS.md)** - Detailed requirements
- **[Test Suite Summary](../TEST_SUITE_SUMMARY.md)** - 46 tests, 100% coverage

---

**For questions or issues, see the [GitHub repository](https://github.com/nexus/attendance-management).**
