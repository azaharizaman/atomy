# Integration Guide: Payroll

This guide provides comprehensive examples for integrating the `Nexus\Payroll` package into Laravel and Symfony applications.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Common Patterns](#common-patterns)
4. [Multi-Country Setup](#multi-country-setup)
5. [Testing](#testing)
6. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/payroll:"*@dev"

# For Malaysia statutory calculations
composer require nexus/payroll-mys-statutory:"*@dev"
```

### Step 2: Create Database Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payroll Components
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('code', 50)->index();
            $table->string('name', 255);
            $table->string('type', 50); // earning, deduction, employer_contribution
            $table->string('calculation_method', 50); // fixed, percentage_of_basic, etc.
            $table->decimal('percentage', 8, 4)->nullable();
            $table->string('formula')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants');
        });
        
        // Employee Component Assignments
        Schema::create('employee_components', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('employee_id', 26)->index();
            $table->string('component_id', 26)->index();
            $table->decimal('amount', 15, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('component_id')->references('id')->on('payroll_components');
        });
        
        // Payslips
        Schema::create('payslips', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('payslip_number', 50)->index();
            $table->string('employee_id', 26)->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_pay', 15, 2);
            $table->decimal('total_deductions', 15, 2);
            $table->decimal('net_pay', 15, 2);
            $table->decimal('employer_contributions', 15, 2);
            $table->string('status', 50)->default('draft');
            $table->json('earnings_breakdown')->nullable();
            $table->json('deductions_breakdown')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'payslip_number']);
            $table->index(['tenant_id', 'period_start', 'period_end']);
            $table->foreign('tenant_id')->references('id')->on('tenants');
        });
    }
};
```

### Step 3: Create Eloquent Models

```php
<?php

declare(strict_types=1);

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Payroll\Contracts\ComponentInterface;
use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;

class PayrollComponent extends Model implements ComponentInterface
{
    use SoftDeletes;
    
    protected $table = 'payroll_components';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id',
        'tenant_id',
        'code',
        'name',
        'type',
        'calculation_method',
        'percentage',
        'formula',
        'is_taxable',
        'is_statutory',
        'is_active',
    ];
    
    protected $casts = [
        'is_taxable' => 'boolean',
        'is_statutory' => 'boolean',
        'is_active' => 'boolean',
        'percentage' => 'float',
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    public function getCode(): string
    {
        return $this->code;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getType(): ComponentType
    {
        return ComponentType::from($this->type);
    }
    
    public function getCalculationMethod(): CalculationMethod
    {
        return CalculationMethod::from($this->calculation_method);
    }
    
    public function isTaxable(): bool
    {
        return $this->is_taxable;
    }
    
    public function isStatutory(): bool
    {
        return $this->is_statutory;
    }
    
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Payroll\Contracts\PayslipInterface;
use Nexus\Payroll\ValueObjects\PayslipStatus;

class Payslip extends Model implements PayslipInterface
{
    use SoftDeletes;
    
    protected $table = 'payslips';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id',
        'tenant_id',
        'payslip_number',
        'employee_id',
        'period_start',
        'period_end',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'employer_contributions',
        'status',
        'earnings_breakdown',
        'deductions_breakdown',
        'metadata',
    ];
    
    protected $casts = [
        'gross_pay' => 'float',
        'total_deductions' => 'float',
        'net_pay' => 'float',
        'employer_contributions' => 'float',
        'earnings_breakdown' => 'array',
        'deductions_breakdown' => 'array',
        'metadata' => 'array',
        'period_start' => 'immutable_date',
        'period_end' => 'immutable_date',
        'created_at' => 'immutable_datetime',
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    public function getPayslipNumber(): string
    {
        return $this->payslip_number;
    }
    
    public function getEmployeeId(): string
    {
        return $this->employee_id;
    }
    
    public function getPeriodStart(): \DateTimeImmutable
    {
        return $this->period_start;
    }
    
    public function getPeriodEnd(): \DateTimeImmutable
    {
        return $this->period_end;
    }
    
    public function getGrossPay(): float
    {
        return $this->gross_pay;
    }
    
    public function getTotalDeductions(): float
    {
        return $this->total_deductions;
    }
    
    public function getNetPay(): float
    {
        return $this->net_pay;
    }
    
    public function getEmployerContributions(): float
    {
        return $this->employer_contributions;
    }
    
    public function getStatus(): PayslipStatus
    {
        return PayslipStatus::from($this->status);
    }
    
    public function getEarningsBreakdown(): array
    {
        return $this->earnings_breakdown ?? [];
    }
    
    public function getDeductionsBreakdown(): array
    {
        return $this->deductions_breakdown ?? [];
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }
}
```

### Step 4: Create Repository Implementation

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Payroll;

use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\ComponentPersistInterface;
use Nexus\Payroll\Contracts\ComponentInterface;
use Nexus\Payroll\ValueObjects\ComponentType;
use App\Models\Payroll\PayrollComponent;
use Symfony\Component\Uid\Ulid;

final readonly class EloquentComponentRepository 
    implements ComponentQueryInterface, ComponentPersistInterface
{
    public function __construct(
        private PayrollComponent $model
    ) {}
    
    public function findById(string $id): ?ComponentInterface
    {
        return $this->model->find($id);
    }
    
    public function findByCode(string $code): ?ComponentInterface
    {
        return $this->model->where('code', $code)->first();
    }
    
    public function getActiveComponents(): array
    {
        return $this->model
            ->where('is_active', true)
            ->get()
            ->all();
    }
    
    public function getByType(ComponentType $type): array
    {
        return $this->model
            ->where('type', $type->value)
            ->where('is_active', true)
            ->get()
            ->all();
    }
    
    public function create(array $data): ComponentInterface
    {
        $data['id'] = $data['id'] ?? (string) new Ulid();
        $data['type'] = $data['type'] instanceof ComponentType 
            ? $data['type']->value 
            : $data['type'];
        $data['calculation_method'] = $data['calculation_method'] instanceof CalculationMethod 
            ? $data['calculation_method']->value 
            : $data['calculation_method'];
            
        return $this->model->create($data);
    }
    
    public function update(string $id, array $data): ComponentInterface
    {
        $component = $this->model->findOrFail($id);
        
        if (isset($data['type']) && $data['type'] instanceof ComponentType) {
            $data['type'] = $data['type']->value;
        }
        if (isset($data['calculation_method']) && $data['calculation_method'] instanceof CalculationMethod) {
            $data['calculation_method'] = $data['calculation_method']->value;
        }
        
        $component->update($data);
        return $component->fresh();
    }
    
    public function delete(string $id): bool
    {
        return $this->model->destroy($id) > 0;
    }
}
```

### Step 5: Create Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\ComponentPersistInterface;
use Nexus\Payroll\Contracts\PayslipQueryInterface;
use Nexus\Payroll\Contracts\PayslipPersistInterface;
use Nexus\Payroll\Contracts\EmployeeComponentQueryInterface;
use Nexus\Payroll\Contracts\EmployeeComponentPersistInterface;
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\Payroll\Services\PayrollEngine;
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\Services\PayslipManager;
use App\Repositories\Payroll\EloquentComponentRepository;
use App\Repositories\Payroll\EloquentPayslipRepository;
use App\Repositories\Payroll\EloquentEmployeeComponentRepository;
use Nexus\PayrollMysStatutory\Calculators\MalaysiaStatutoryCalculator;

class PayrollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Component Repository (CQRS)
        $this->app->singleton(ComponentQueryInterface::class, function ($app) {
            return $app->make(EloquentComponentRepository::class);
        });
        $this->app->singleton(ComponentPersistInterface::class, function ($app) {
            return $app->make(EloquentComponentRepository::class);
        });
        
        // Payslip Repository (CQRS)
        $this->app->singleton(PayslipQueryInterface::class, function ($app) {
            return $app->make(EloquentPayslipRepository::class);
        });
        $this->app->singleton(PayslipPersistInterface::class, function ($app) {
            return $app->make(EloquentPayslipRepository::class);
        });
        
        // Employee Component Repository (CQRS)
        $this->app->singleton(EmployeeComponentQueryInterface::class, function ($app) {
            return $app->make(EloquentEmployeeComponentRepository::class);
        });
        $this->app->singleton(EmployeeComponentPersistInterface::class, function ($app) {
            return $app->make(EloquentEmployeeComponentRepository::class);
        });
        
        // Statutory Calculator (country-specific)
        $this->app->singleton(StatutoryCalculatorInterface::class, function ($app) {
            return $app->make(MalaysiaStatutoryCalculator::class);
        });
        
        // Services (auto-wired)
        $this->app->singleton(PayrollEngine::class);
        $this->app->singleton(ComponentManager::class);
        $this->app->singleton(PayslipManager::class);
    }
}
```

### Step 6: Create Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Payroll\Services\PayrollEngine;
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\Exceptions\ComponentNotFoundException;
use Nexus\Payroll\Exceptions\PayrollException;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollEngine $payrollEngine,
        private readonly ComponentManager $componentManager
    ) {}
    
    /**
     * Process payroll for a period
     */
    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'filters' => 'array',
        ]);
        
        try {
            $payslips = $this->payrollEngine->processPeriod(
                tenantId: $request->user()->tenant_id,
                periodStart: $validated['period_start'],
                periodEnd: $validated['period_end'],
                filters: $validated['filters'] ?? []
            );
            
            return response()->json([
                'success' => true,
                'count' => count($payslips),
                'payslips' => $payslips,
            ]);
        } catch (PayrollException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Create a new component
     */
    public function createComponent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction,employer_contribution',
            'calculation_method' => 'required|in:fixed,percentage_of_basic,percentage_of_gross,formula',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'is_taxable' => 'boolean',
            'is_statutory' => 'boolean',
        ]);
        
        $validated['tenant_id'] = $request->user()->tenant_id;
        
        $component = $this->componentManager->createComponent($validated);
        
        return response()->json([
            'success' => true,
            'component' => $component,
        ], 201);
    }
    
    /**
     * Get component by ID
     */
    public function getComponent(string $id): JsonResponse
    {
        try {
            $component = $this->componentManager->getComponent($id);
            
            return response()->json([
                'success' => true,
                'component' => $component,
            ]);
        } catch (ComponentNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/payroll:"*@dev"
composer require nexus/payroll-mys-statutory:"*@dev"
```

### Step 2: Create Doctrine Entity

```php
<?php

declare(strict_types=1);

namespace App\Entity\Payroll;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Payroll\Contracts\ComponentInterface;
use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;

#[ORM\Entity(repositoryClass: ComponentRepository::class)]
#[ORM\Table(name: 'payroll_components')]
class Component implements ComponentInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;
    
    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;
    
    #[ORM\Column(type: 'string', length: 50)]
    private string $code;
    
    #[ORM\Column(type: 'string', length: 255)]
    private string $name;
    
    #[ORM\Column(type: 'string', length: 50)]
    private string $type;
    
    #[ORM\Column(type: 'string', length: 50)]
    private string $calculationMethod;
    
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $percentage = null;
    
    #[ORM\Column(type: 'boolean')]
    private bool $isTaxable = true;
    
    #[ORM\Column(type: 'boolean')]
    private bool $isStatutory = false;
    
    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenantId;
    }
    
    public function getCode(): string
    {
        return $this->code;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getType(): ComponentType
    {
        return ComponentType::from($this->type);
    }
    
    public function getCalculationMethod(): CalculationMethod
    {
        return CalculationMethod::from($this->calculationMethod);
    }
    
    public function isTaxable(): bool
    {
        return $this->isTaxable;
    }
    
    public function isStatutory(): bool
    {
        return $this->isStatutory;
    }
    
    public function isActive(): bool
    {
        return $this->isActive;
    }
}
```

### Step 3: Create Repository

```php
<?php

declare(strict_types=1);

namespace App\Repository\Payroll;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\ComponentPersistInterface;
use Nexus\Payroll\Contracts\ComponentInterface;
use Nexus\Payroll\ValueObjects\ComponentType;
use App\Entity\Payroll\Component;
use Symfony\Component\Uid\Ulid;

class ComponentRepository extends ServiceEntityRepository 
    implements ComponentQueryInterface, ComponentPersistInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Component::class);
    }
    
    public function findById(string $id): ?ComponentInterface
    {
        return $this->find($id);
    }
    
    public function findByCode(string $code): ?ComponentInterface
    {
        return $this->findOneBy(['code' => $code]);
    }
    
    public function getActiveComponents(): array
    {
        return $this->findBy(['isActive' => true]);
    }
    
    public function getByType(ComponentType $type): array
    {
        return $this->findBy([
            'type' => $type->value,
            'isActive' => true,
        ]);
    }
    
    public function create(array $data): ComponentInterface
    {
        $component = new Component();
        // Set properties from $data
        
        $this->getEntityManager()->persist($component);
        $this->getEntityManager()->flush();
        
        return $component;
    }
    
    public function update(string $id, array $data): ComponentInterface
    {
        $component = $this->find($id);
        // Update properties
        
        $this->getEntityManager()->flush();
        
        return $component;
    }
    
    public function delete(string $id): bool
    {
        $component = $this->find($id);
        if ($component) {
            $this->getEntityManager()->remove($component);
            $this->getEntityManager()->flush();
            return true;
        }
        return false;
    }
}
```

### Step 4: Configure Services

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
    
    # Repositories
    App\Repository\Payroll\ComponentRepository:
        tags: ['doctrine.repository_service']
    
    Nexus\Payroll\Contracts\ComponentQueryInterface:
        alias: App\Repository\Payroll\ComponentRepository
    
    Nexus\Payroll\Contracts\ComponentPersistInterface:
        alias: App\Repository\Payroll\ComponentRepository
    
    Nexus\Payroll\Contracts\PayslipQueryInterface:
        alias: App\Repository\Payroll\PayslipRepository
    
    Nexus\Payroll\Contracts\PayslipPersistInterface:
        alias: App\Repository\Payroll\PayslipRepository
    
    Nexus\Payroll\Contracts\EmployeeComponentQueryInterface:
        alias: App\Repository\Payroll\EmployeeComponentRepository
    
    Nexus\Payroll\Contracts\EmployeeComponentPersistInterface:
        alias: App\Repository\Payroll\EmployeeComponentRepository
    
    # Statutory Calculator
    Nexus\Payroll\Contracts\StatutoryCalculatorInterface:
        class: Nexus\PayrollMysStatutory\Calculators\MalaysiaStatutoryCalculator
    
    # Services
    Nexus\Payroll\Services\PayrollEngine: ~
    Nexus\Payroll\Services\ComponentManager: ~
    Nexus\Payroll\Services\PayslipManager: ~
```

---

## Common Patterns

### Pattern 1: Tenant-Scoped Queries

Always scope queries by tenant:

```php
// In your repository implementation
public function getActiveComponents(): array
{
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    return $this->model
        ->where('tenant_id', $tenantId)
        ->where('is_active', true)
        ->get()
        ->all();
}
```

### Pattern 2: Payslip Number Generation

Use `Nexus\Sequencing` for payslip numbers:

```php
use Nexus\Sequencing\Contracts\SequencingManagerInterface;

final readonly class PayslipNumberGenerator
{
    public function __construct(
        private SequencingManagerInterface $sequencing
    ) {}
    
    public function generate(string $tenantId): string
    {
        return $this->sequencing->getNext('payslip', [
            'tenant_id' => $tenantId,
        ]);
        // Returns: PS-2025-00001
    }
}
```

### Pattern 3: Audit Logging

Integrate with `Nexus\AuditLogger`:

```php
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;

public function approvePayslip(string $id): PayslipInterface
{
    $payslip = $this->payslipManager->updatePayslipStatus(
        $id, 
        PayslipStatus::APPROVED
    );
    
    $this->auditLogger->log(
        entityId: $id,
        action: 'payslip.approved',
        description: sprintf(
            'Payslip %s approved for employee %s',
            $payslip->getPayslipNumber(),
            $payslip->getEmployeeId()
        )
    );
    
    return $payslip;
}
```

### Pattern 4: GL Journal Posting

Integrate with `Nexus\Finance`:

```php
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;

public function postPayrollToGL(array $payslips): void
{
    $entries = [];
    
    foreach ($payslips as $payslip) {
        // Salary Expense
        $entries[] = [
            'account_code' => '5100', // Salary Expense
            'debit' => $payslip->getGrossPay(),
            'credit' => 0,
        ];
        
        // EPF Payable
        $entries[] = [
            'account_code' => '2200', // EPF Payable
            'debit' => 0,
            'credit' => $payslip->getDeductionsBreakdown()['EPF_EMPLOYEE'] ?? 0,
        ];
        
        // Net Salary Payable
        $entries[] = [
            'account_code' => '2100', // Salary Payable
            'debit' => 0,
            'credit' => $payslip->getNetPay(),
        ];
    }
    
    $this->glManager->postJournalEntry([
        'entries' => $entries,
        'description' => 'Payroll for period',
        'reference' => 'PAYROLL-2025-01',
    ]);
}
```

---

## Multi-Country Setup

For organizations operating in multiple countries:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\PayrollMysStatutory\Calculators\MalaysiaStatutoryCalculator;
use App\Payroll\SingaporeStatutoryCalculator;

class PayrollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Determine country from tenant or configuration
        $this->app->singleton(StatutoryCalculatorInterface::class, function ($app) {
            $countryCode = $this->resolveCountryCode();
            
            return match ($countryCode) {
                'MY' => $app->make(MalaysiaStatutoryCalculator::class),
                'SG' => $app->make(SingaporeStatutoryCalculator::class),
                'ID' => $app->make(IndonesiaStatutoryCalculator::class),
                default => throw new \RuntimeException("Unsupported country: {$countryCode}"),
            };
        });
    }
    
    private function resolveCountryCode(): string
    {
        // Get from tenant configuration
        $tenant = app('tenant.context')->getCurrentTenant();
        return $tenant->getCountryCode() ?? 'MY';
    }
}
```

---

## Testing

### Unit Testing Package Logic

```php
<?php

namespace Tests\Unit\Payroll;

use PHPUnit\Framework\TestCase;
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\ComponentPersistInterface;
use Nexus\Payroll\Contracts\ComponentInterface;
use Nexus\Payroll\Exceptions\ComponentNotFoundException;

class ComponentManagerTest extends TestCase
{
    public function test_get_component_returns_component(): void
    {
        $mockComponent = $this->createMock(ComponentInterface::class);
        $mockComponent->method('getId')->willReturn('comp-001');
        
        $query = $this->createMock(ComponentQueryInterface::class);
        $query->expects($this->once())
            ->method('findById')
            ->with('comp-001')
            ->willReturn($mockComponent);
        
        $persist = $this->createMock(ComponentPersistInterface::class);
        
        $manager = new ComponentManager($query, $persist);
        
        $result = $manager->getComponent('comp-001');
        
        $this->assertSame($mockComponent, $result);
    }
    
    public function test_get_component_throws_when_not_found(): void
    {
        $query = $this->createMock(ComponentQueryInterface::class);
        $query->method('findById')->willReturn(null);
        
        $persist = $this->createMock(ComponentPersistInterface::class);
        
        $manager = new ComponentManager($query, $persist);
        
        $this->expectException(ComponentNotFoundException::class);
        
        $manager->getComponent('non-existent');
    }
}
```

### Integration Testing (Laravel)

```php
<?php

namespace Tests\Feature\Payroll;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Payroll\PayrollComponent;

class PayrollApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_component(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/payroll/components', [
                'code' => 'BASIC',
                'name' => 'Basic Salary',
                'type' => 'earning',
                'calculation_method' => 'fixed',
                'is_taxable' => true,
            ]);
        
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('component.code', 'BASIC');
        
        $this->assertDatabaseHas('payroll_components', [
            'code' => 'BASIC',
            'tenant_id' => $user->tenant_id,
        ]);
    }
    
    public function test_can_process_payroll(): void
    {
        // Setup test data
        $user = User::factory()->create();
        $component = PayrollComponent::factory()->create([
            'tenant_id' => $user->tenant_id,
            'code' => 'BASIC',
        ]);
        
        // Assign component to employee...
        
        $response = $this->actingAs($user)
            ->postJson('/api/payroll/process', [
                'period_start' => '2025-01-01',
                'period_end' => '2025-01-31',
            ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
```

---

## Troubleshooting

### Issue: Interface not bound

**Error:**
```
Target interface [Nexus\Payroll\Contracts\ComponentQueryInterface] is not instantiable.
```

**Solution:**
Ensure service provider is registered in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\PayrollServiceProvider::class,
],
```

### Issue: Statutory calculator not found

**Error:**
```
Target interface [Nexus\Payroll\Contracts\StatutoryCalculatorInterface] is not instantiable.
```

**Solution:**
1. Install country package: `composer require nexus/payroll-mys-statutory`
2. Bind in service provider (see Step 5 above)

### Issue: CQRS interface conflict

**Error:**
```
Cannot use Nexus\Payroll\Contracts\ComponentRepositoryInterface and ComponentQueryInterface
```

**Solution:**
Use the new CQRS interfaces (`*QueryInterface` + `*PersistInterface`) instead of the deprecated combined `*RepositoryInterface`.

### Issue: Empty payslips returned

**Causes:**
1. No active employee components for the period
2. Components have wrong effective dates
3. Tenant ID mismatch

**Debug:**
```php
// Check active components
$components = $componentQuery->getActiveComponents();
dump($components);

// Check employee components
$empComponents = $employeeComponentQuery->getActiveComponentsForEmployee(
    $employeeId,
    new \DateTimeImmutable($periodStart)
);
dump($empComponents);
```

---

## Performance Optimization

### Bulk Processing

For large payrolls (1000+ employees):

```php
// Process in chunks
$employees = Employee::where('tenant_id', $tenantId)
    ->chunk(100, function ($chunk) use ($periodStart, $periodEnd) {
        foreach ($chunk as $employee) {
            $this->payrollEngine->processEmployee(
                $employee->tenant_id,
                $employee->id,
                $periodStart,
                $periodEnd
            );
        }
    });
```

### Caching Components

```php
// Cache active components (they don't change often)
$components = Cache::remember(
    "payroll.components.{$tenantId}",
    3600, // 1 hour
    fn() => $this->componentQuery->getActiveComponents()
);
```

---

## Next Steps

- Read [API Reference](api-reference.md) for complete interface documentation
- See [Examples](examples/) for working code samples
- Review root `ARCHITECTURE.md` for overall system design
