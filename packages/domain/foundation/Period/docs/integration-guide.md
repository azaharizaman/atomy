# Integration Guide: Period

This guide provides comprehensive examples for integrating the Nexus Period package into Laravel and Symfony applications. All examples follow framework-agnostic principles with concrete implementations.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Common Patterns](#common-patterns)
4. [Cross-Package Integration](#cross-package-integration)
5. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/period:"*@dev"
```

---

### Step 2: Create Database Migration

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('type'); // accounting, inventory, payroll, manufacturing
            $table->string('status'); // pending, open, closed, locked
            $table->date('start_date');
            $table->date('end_date');
            $table->string('fiscal_year', 10);
            $table->string('name', 50); // e.g., "JAN-2024", "2024-Q1"
            $table->text('description')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_by', 26)->nullable();
            $table->text('closed_reason')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['tenant_id', 'type', 'status'], 'periods_tenant_type_status');
            $table->index(['tenant_id', 'type', 'start_date', 'end_date'], 'periods_tenant_type_dates');
            $table->index(['tenant_id', 'fiscal_year']);
            
            // Prevent overlapping periods
            $table->unique(['tenant_id', 'type', 'start_date']);
            $table->unique(['tenant_id', 'type', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
```

---

### Step 3: Create Eloquent Model

```php
<?php

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;

class Period extends Model implements PeriodInterface
{
    use HasUlids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'fiscal_year',
        'name',
        'description',
        'closed_at',
        'closed_by',
        'closed_reason',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'closed_at' => 'datetime',
    ];

    // ========================================
    // PeriodInterface Implementation
    // ========================================

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): PeriodType
    {
        return PeriodType::from($this->type);
    }

    public function getStatus(): PeriodStatus
    {
        return PeriodStatus::from($this->status);
    }

    public function getStartDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->start_date);
    }

    public function getEndDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->end_date);
    }

    public function getFiscalYear(): string
    {
        return $this->fiscal_year;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function containsDate(DateTimeImmutable $date): bool
    {
        return $date >= $this->getStartDate() && $date <= $this->getEndDate();
    }

    public function isPostingAllowed(): bool
    {
        return $this->getStatus()->isPostingAllowed();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }

    // ========================================
    // Eloquent Scopes
    // ========================================

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOfType($query, PeriodType $type)
    {
        return $query->where('type', $type->value);
    }

    public function scopeWithStatus($query, PeriodStatus $status)
    {
        return $query->where('status', $status->value);
    }

    public function scopeContainingDate($query, DateTimeImmutable $date)
    {
        return $query
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }
}
```

---

### Step 4: Create Repository Implementation

```php
<?php

namespace App\Repositories;

use DateTimeImmutable;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Contracts\PeriodRepositoryInterface;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\PeriodNotFoundException;
use Nexus\Tenant\Contracts\TenantContextInterface;
use App\Models\Period;

final readonly class EloquentPeriodRepository implements PeriodRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    public function findById(string $id): PeriodInterface
    {
        $period = Period::forTenant($this->getTenantId())
            ->find($id);
            
        if ($period === null) {
            throw PeriodNotFoundException::forId($id);
        }
        
        return $period;
    }

    public function findOpenByType(PeriodType $type): ?PeriodInterface
    {
        return Period::forTenant($this->getTenantId())
            ->ofType($type)
            ->withStatus(PeriodStatus::Open)
            ->first();
    }

    public function findByDate(DateTimeImmutable $date, PeriodType $type): ?PeriodInterface
    {
        return Period::forTenant($this->getTenantId())
            ->ofType($type)
            ->containingDate($date)
            ->first();
    }

    public function findByType(PeriodType $type): array
    {
        return Period::forTenant($this->getTenantId())
            ->ofType($type)
            ->orderBy('start_date')
            ->get()
            ->all();
    }

    public function findByFiscalYear(PeriodType $type, string $fiscalYear): array
    {
        return Period::forTenant($this->getTenantId())
            ->ofType($type)
            ->where('fiscal_year', $fiscalYear)
            ->orderBy('start_date')
            ->get()
            ->all();
    }

    public function save(PeriodInterface $period): void
    {
        if ($period instanceof Period) {
            $period->save();
        }
    }

    private function getTenantId(): string
    {
        return $this->tenantContext->getCurrentTenantId();
    }
}
```

---

### Step 5: Create Cache Repository

```php
<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Nexus\Period\Contracts\CacheRepositoryInterface;

final readonly class LaravelCacheRepository implements CacheRepositoryInterface
{
    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        Cache::put($key, $value, $ttl);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
```

---

### Step 6: Create Authorization Service

```php
<?php

namespace App\Services\Period;

use Nexus\Period\Contracts\AuthorizationInterface;
use Nexus\Identity\Contracts\AuthorizationManagerInterface;

final readonly class PeriodAuthorization implements AuthorizationInterface
{
    private const REOPEN_PERMISSION = 'period.reopen';

    public function __construct(
        private AuthorizationManagerInterface $authorization
    ) {}

    public function canReopenPeriod(string $userId): bool
    {
        return $this->authorization->userHasPermission($userId, self::REOPEN_PERMISSION);
    }
}
```

---

### Step 7: Create Audit Logger

```php
<?php

namespace App\Services\Period;

use Nexus\Period\Contracts\AuditLoggerInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;

final readonly class PeriodAuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private AuditLogManagerInterface $auditLogger
    ) {}

    public function log(string $entityId, string $action, string $description): void
    {
        $this->auditLogger->log(
            entityId: $entityId,
            action: $action,
            description: $description
        );
    }
}
```

---

### Step 8: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Period\Contracts\{
    PeriodRepositoryInterface,
    CacheRepositoryInterface,
    AuthorizationInterface,
    AuditLoggerInterface,
    PeriodManagerInterface
};
use Nexus\Period\Services\PeriodManager;
use App\Repositories\{
    EloquentPeriodRepository,
    LaravelCacheRepository
};
use App\Services\Period\{
    PeriodAuthorization,
    PeriodAuditLogger
};

class PeriodServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(
            PeriodRepositoryInterface::class,
            EloquentPeriodRepository::class
        );
        
        $this->app->singleton(
            CacheRepositoryInterface::class,
            LaravelCacheRepository::class
        );
        
        // Service bindings
        $this->app->singleton(
            AuthorizationInterface::class,
            PeriodAuthorization::class
        );
        
        $this->app->singleton(
            AuditLoggerInterface::class,
            PeriodAuditLogger::class
        );
        
        // Main manager (auto-resolved)
        $this->app->singleton(
            PeriodManagerInterface::class,
            PeriodManager::class
        );
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\PeriodServiceProvider::class,
],
```

---

### Step 9: Create Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Exceptions\{
    PeriodNotFoundException,
    InvalidPeriodStatusException,
    PeriodReopeningUnauthorizedException
};

class PeriodController extends Controller
{
    public function __construct(
        private readonly PeriodManagerInterface $periodManager
    ) {}

    /**
     * List all periods for a type
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:accounting,inventory,payroll,manufacturing',
            'fiscal_year' => 'nullable|string|size:4',
        ]);

        $type = PeriodType::from($request->input('type'));
        $fiscalYear = $request->input('fiscal_year');

        $periods = $this->periodManager->listPeriods($type, $fiscalYear);

        return response()->json([
            'data' => array_map(fn($p) => $this->formatPeriod($p), $periods),
        ]);
    }

    /**
     * Get single period
     */
    public function show(string $id): JsonResponse
    {
        try {
            $period = $this->periodManager->findById($id);
            return response()->json(['data' => $this->formatPeriod($period)]);
        } catch (PeriodNotFoundException $e) {
            return response()->json(['error' => 'Period not found'], 404);
        }
    }

    /**
     * Get current open period for type
     */
    public function current(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:accounting,inventory,payroll,manufacturing',
        ]);

        $type = PeriodType::from($request->input('type'));
        $period = $this->periodManager->getOpenPeriod($type);

        if ($period === null) {
            return response()->json(['error' => 'No open period found'], 404);
        }

        return response()->json(['data' => $this->formatPeriod($period)]);
    }

    /**
     * Close a period
     */
    public function close(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->periodManager->closePeriod(
                periodId: $id,
                reason: $request->input('reason'),
                userId: $request->user()->id
            );

            return response()->json(['message' => 'Period closed successfully']);

        } catch (PeriodNotFoundException $e) {
            return response()->json(['error' => 'Period not found'], 404);
        } catch (InvalidPeriodStatusException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Reopen a period
     */
    public function reopen(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->periodManager->reopenPeriod(
                periodId: $id,
                reason: $request->input('reason'),
                userId: $request->user()->id
            );

            return response()->json(['message' => 'Period reopened successfully']);

        } catch (PeriodNotFoundException $e) {
            return response()->json(['error' => 'Period not found'], 404);
        } catch (PeriodReopeningUnauthorizedException $e) {
            return response()->json(['error' => 'Unauthorized to reopen periods'], 403);
        } catch (InvalidPeriodStatusException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Validate if posting is allowed
     */
    public function validatePosting(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:accounting,inventory,payroll,manufacturing',
        ]);

        $date = new \DateTimeImmutable($request->input('date'));
        $type = PeriodType::from($request->input('type'));

        $allowed = $this->periodManager->isPostingAllowed($date, $type);

        return response()->json([
            'allowed' => $allowed,
            'date' => $date->format('Y-m-d'),
            'type' => $type->value,
        ]);
    }

    private function formatPeriod($period): array
    {
        return [
            'id' => $period->getId(),
            'type' => $period->getType()->value,
            'type_label' => $period->getType()->label(),
            'status' => $period->getStatus()->value,
            'status_label' => $period->getStatus()->label(),
            'name' => $period->getName(),
            'fiscal_year' => $period->getFiscalYear(),
            'start_date' => $period->getStartDate()->format('Y-m-d'),
            'end_date' => $period->getEndDate()->format('Y-m-d'),
            'description' => $period->getDescription(),
            'is_posting_allowed' => $period->isPostingAllowed(),
            'created_at' => $period->getCreatedAt()->format('c'),
            'updated_at' => $period->getUpdatedAt()->format('c'),
        ];
    }
}
```

---

### Step 10: Define Routes

```php
// routes/api.php
use App\Http\Controllers\Api\PeriodController;

Route::middleware('auth:sanctum')->prefix('periods')->group(function () {
    Route::get('/', [PeriodController::class, 'index']);
    Route::get('/current', [PeriodController::class, 'current']);
    Route::get('/{id}', [PeriodController::class, 'show']);
    Route::post('/{id}/close', [PeriodController::class, 'close']);
    Route::post('/{id}/reopen', [PeriodController::class, 'reopen']);
    Route::post('/validate-posting', [PeriodController::class, 'validatePosting']);
});
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/period:"*@dev"
```

---

### Step 2: Create Doctrine Entity

```php
<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: PeriodRepository::class)]
#[ORM\Table(name: 'periods')]
#[ORM\Index(columns: ['tenant_id', 'type', 'status'], name: 'periods_tenant_type_status')]
class Period implements PeriodInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $startDate;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $endDate;

    #[ORM\Column(type: 'string', length: 10)]
    private string $fiscalYear;

    #[ORM\Column(type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $tenantId,
        PeriodType $type,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $fiscalYear,
        string $name,
        ?string $description = null
    ) {
        $this->id = (string) new Ulid();
        $this->tenantId = $tenantId;
        $this->type = $type->value;
        $this->status = PeriodStatus::Pending->value;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->fiscalYear = $fiscalYear;
        $this->name = $name;
        $this->description = $description;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // PeriodInterface implementation...
    public function getId(): string { return $this->id; }
    public function getType(): PeriodType { return PeriodType::from($this->type); }
    public function getStatus(): PeriodStatus { return PeriodStatus::from($this->status); }
    public function getStartDate(): DateTimeImmutable { return $this->startDate; }
    public function getEndDate(): DateTimeImmutable { return $this->endDate; }
    public function getFiscalYear(): string { return $this->fiscalYear; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function containsDate(DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function isPostingAllowed(): bool
    {
        return $this->getStatus()->isPostingAllowed();
    }

    public function setStatus(PeriodStatus $status): void
    {
        $this->status = $status->value;
        $this->updatedAt = new DateTimeImmutable();
    }
}
```

---

### Step 3: Create Repository

```php
<?php

namespace App\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Contracts\PeriodRepositoryInterface;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\PeriodNotFoundException;
use App\Entity\Period;
use App\Service\TenantContext;

class PeriodRepository extends ServiceEntityRepository implements PeriodRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly TenantContext $tenantContext
    ) {
        parent::__construct($registry, Period::class);
    }

    public function findById(string $id): PeriodInterface
    {
        $period = $this->find($id);
        
        if ($period === null) {
            throw PeriodNotFoundException::forId($id);
        }
        
        return $period;
    }

    public function findOpenByType(PeriodType $type): ?PeriodInterface
    {
        return $this->createQueryBuilder('p')
            ->where('p.tenantId = :tenantId')
            ->andWhere('p.type = :type')
            ->andWhere('p.status = :status')
            ->setParameter('tenantId', $this->tenantContext->getTenantId())
            ->setParameter('type', $type->value)
            ->setParameter('status', PeriodStatus::Open->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByDate(DateTimeImmutable $date, PeriodType $type): ?PeriodInterface
    {
        return $this->createQueryBuilder('p')
            ->where('p.tenantId = :tenantId')
            ->andWhere('p.type = :type')
            ->andWhere('p.startDate <= :date')
            ->andWhere('p.endDate >= :date')
            ->setParameter('tenantId', $this->tenantContext->getTenantId())
            ->setParameter('type', $type->value)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByType(PeriodType $type): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.tenantId = :tenantId')
            ->andWhere('p.type = :type')
            ->orderBy('p.startDate', 'ASC')
            ->setParameter('tenantId', $this->tenantContext->getTenantId())
            ->setParameter('type', $type->value)
            ->getQuery()
            ->getResult();
    }

    public function save(PeriodInterface $period): void
    {
        $this->getEntityManager()->persist($period);
        $this->getEntityManager()->flush();
    }
}
```

---

### Step 4: Configure Services

```yaml
# config/services.yaml
services:
    # Period Repository
    Nexus\Period\Contracts\PeriodRepositoryInterface:
        class: App\Repository\PeriodRepository

    # Cache Repository
    Nexus\Period\Contracts\CacheRepositoryInterface:
        class: App\Service\SymfonyCacheRepository

    # Authorization
    Nexus\Period\Contracts\AuthorizationInterface:
        class: App\Service\PeriodAuthorization

    # Audit Logger
    Nexus\Period\Contracts\AuditLoggerInterface:
        class: App\Service\PeriodAuditLogger

    # Period Manager
    Nexus\Period\Contracts\PeriodManagerInterface:
        class: Nexus\Period\Services\PeriodManager
        arguments:
            $repository: '@Nexus\Period\Contracts\PeriodRepositoryInterface'
            $cache: '@Nexus\Period\Contracts\CacheRepositoryInterface'
            $authorization: '@Nexus\Period\Contracts\AuthorizationInterface'
            $auditLogger: '@Nexus\Period\Contracts\AuditLoggerInterface'
```

---

## Common Patterns

### Pattern 1: Dependency Injection

Always inject interfaces, never concrete classes:

```php
// ✅ CORRECT - Inject interface
public function __construct(
    private readonly PeriodManagerInterface $periodManager
) {}

// ❌ WRONG - Inject concrete class
public function __construct(
    private readonly PeriodManager $periodManager
) {}
```

---

### Pattern 2: Transaction Posting Validation

```php
final readonly class InvoiceService
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function post(Invoice $invoice): void
    {
        $transactionDate = $invoice->getInvoiceDate();
        
        // Validate period is open (< 5ms with caching)
        if (!$this->periodManager->isPostingAllowed($transactionDate, PeriodType::Accounting)) {
            throw new \DomainException(
                "Cannot post invoice: Accounting period is not open for {$transactionDate->format('Y-m-d')}"
            );
        }
        
        // Post the invoice
        $invoice->markAsPosted();
        $this->invoiceRepository->save($invoice);
    }
}
```

---

### Pattern 3: Month-End Close Process

```php
final readonly class MonthEndCloseService
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        private ReconciliationService $reconciliation
    ) {}

    public function performClose(string $periodId, string $userId): void
    {
        // 1. Validate reconciliation is complete
        $period = $this->periodManager->findById($periodId);
        
        if (!$this->reconciliation->isComplete($period)) {
            throw new \DomainException('Cannot close period: reconciliation incomplete');
        }
        
        // 2. Close the period
        $this->periodManager->closePeriod(
            periodId: $periodId,
            reason: 'Month-end close completed',
            userId: $userId
        );
        
        // 3. Create next period
        $this->periodManager->createNextPeriod($period->getType());
    }
}
```

---

### Pattern 4: Multi-Tenancy Scoping

All repositories should automatically scope by tenant:

```php
final readonly class EloquentPeriodRepository implements PeriodRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    public function findOpenByType(PeriodType $type): ?PeriodInterface
    {
        // Always scope by tenant - this is automatic
        return Period::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('type', $type->value)
            ->where('status', PeriodStatus::Open->value)
            ->first();
    }
}
```

---

## Cross-Package Integration

### Integration with Nexus\Finance (General Ledger)

```php
final readonly class JournalEntryService
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        private GeneralLedgerManagerInterface $glManager
    ) {}

    public function post(JournalEntry $entry): void
    {
        // Validate accounting period
        if (!$this->periodManager->isPostingAllowed($entry->getDate(), PeriodType::Accounting)) {
            throw new PostingPeriodClosedException('Accounting period is closed');
        }
        
        // Post to GL
        $this->glManager->post($entry);
    }
}
```

---

### Integration with Nexus\Inventory (Stock Management)

```php
final readonly class StockAdjustmentService
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        private StockManagerInterface $stockManager
    ) {}

    public function adjust(StockAdjustment $adjustment): void
    {
        // Validate inventory period
        if (!$this->periodManager->isPostingAllowed($adjustment->getDate(), PeriodType::Inventory)) {
            throw new PostingPeriodClosedException('Inventory period is closed');
        }
        
        // Perform adjustment
        $this->stockManager->adjust($adjustment);
    }
}
```

---

## Troubleshooting

### Issue: Interface Not Bound

**Error:**
```
Target interface [Nexus\Period\Contracts\PeriodRepositoryInterface] is not instantiable.
```

**Solution:**
Ensure your service provider binds all interfaces:

```php
$this->app->singleton(PeriodRepositoryInterface::class, EloquentPeriodRepository::class);
$this->app->singleton(CacheRepositoryInterface::class, LaravelCacheRepository::class);
$this->app->singleton(AuthorizationInterface::class, PeriodAuthorization::class);
$this->app->singleton(AuditLoggerInterface::class, PeriodAuditLogger::class);
```

---

### Issue: Slow Validation Performance

**Symptom:** `isPostingAllowed()` takes > 5ms

**Solution:**
1. Ensure Redis is configured (not file/array cache)
2. Check cache hit rate with `php artisan tinker`:

```php
Cache::get('period:open:accounting'); // Should return cached period
```

3. Verify index exists:
```sql
SHOW INDEX FROM periods WHERE Key_name = 'periods_tenant_type_status';
```

---

### Issue: Tenant Context Missing

**Error:**
```
Call to a member function getCurrentTenantId() on null
```

**Solution:**
Ensure `Nexus\Tenant` package is installed and middleware is active:

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        \App\Http\Middleware\TenantMiddleware::class, // Add this
        // ...
    ],
];
```

---

### Issue: Cannot Reopen Locked Period

**Error:**
```
Cannot transition from locked to open
```

**Explanation:** This is correct behavior. Locked periods are permanently sealed for compliance. Once a period is locked (e.g., after year-end audit), it cannot be reopened.

**Solution:** If adjustments are needed for a locked period, create adjustment entries in a current open period instead.

---

**Last Updated:** 2024-11-27
