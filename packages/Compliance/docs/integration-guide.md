# Integration Guide: Compliance

This guide provides comprehensive integration examples for the Nexus\Compliance package with Laravel and Symfony frameworks.

## Table of Contents

- [Laravel Integration](#laravel-integration)
- [Symfony Integration](#symfony-integration)
- [Multi-Tenant Integration](#multi-tenant-integration)
- [Event-Driven Architecture](#event-driven-architecture)
- [Testing Integration](#testing-integration)

---

## Laravel Integration

### Complete Laravel Setup

#### 1. Database Migrations

Create migrations for all compliance-related tables:

```bash
php artisan make:migration create_compliance_tables
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
        Schema::create('compliance_schemes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('scheme_name');
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();
            
            $table->unique(['tenant_id', 'scheme_name']);
            $table->index(['tenant_id', 'is_active']);
            
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
        
        Schema::create('sod_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('rule_name');
            $table->string('transaction_type');
            $table->string('severity_level'); // low, medium, high, critical
            $table->string('creator_role');
            $table->string('approver_role');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'transaction_type']);
            $table->index(['tenant_id', 'is_active']);
            
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
        
        Schema::create('sod_violations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('rule_id');
            $table->ulid('transaction_id');
            $table->string('transaction_type');
            $table->ulid('creator_id');
            $table->ulid('approver_id');
            $table->timestamp('violated_at');
            $table->timestamps();
            
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('rule_id')
                ->references('id')
                ->on('sod_rules')
                ->onDelete('cascade');
            
            $table->index(['tenant_id', 'violated_at']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('sod_violations');
        Schema::dropIfExists('sod_rules');
        Schema::dropIfExists('compliance_schemes');
    }
};
```

#### 2. Eloquent Models

**ComplianceScheme Model:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;

final class ComplianceScheme extends Model implements ComplianceSchemeInterface
{
    use HasUlids;
    
    protected $fillable = [
        'tenant_id',
        'scheme_name',
        'is_active',
        'activated_at',
        'configuration',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'configuration' => 'array',
    ];
    
    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    
    // ComplianceSchemeInterface implementation
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    public function getSchemeName(): string
    {
        return $this->scheme_name;
    }
    
    public function isActive(): bool
    {
        return $this->is_active;
    }
    
    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }
    
    public function activate(array $configuration): void
    {
        $this->update([
            'is_active' => true,
            'activated_at' => now(),
            'configuration' => $configuration,
        ]);
    }
    
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
```

**SodRule Model:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Compliance\Contracts\SodRuleInterface;
use Nexus\Compliance\ValueObjects\SeverityLevel;

final class SodRule extends Model implements SodRuleInterface
{
    use HasUlids;
    
    protected $fillable = [
        'tenant_id',
        'rule_name',
        'transaction_type',
        'severity_level',
        'creator_role',
        'approver_role',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'severity_level' => SeverityLevel::class,
    ];
    
    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function violations()
    {
        return $this->hasMany(SodViolation::class, 'rule_id');
    }
    
    // SodRuleInterface implementation
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    public function getRuleName(): string
    {
        return $this->rule_name;
    }
    
    public function getTransactionType(): string
    {
        return $this->transaction_type;
    }
    
    public function getSeverityLevel(): SeverityLevel
    {
        return $this->severity_level;
    }
    
    public function getCreatorRole(): string
    {
        return $this->creator_role;
    }
    
    public function getApproverRole(): string
    {
        return $this->approver_role;
    }
    
    public function isActive(): bool
    {
        return $this->is_active;
    }
    
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
```

**SodViolation Model:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Compliance\Contracts\SodViolationInterface;

final class SodViolation extends Model implements SodViolationInterface
{
    use HasUlids;
    
    protected $fillable = [
        'tenant_id',
        'rule_id',
        'transaction_id',
        'transaction_type',
        'creator_id',
        'approver_id',
        'violated_at',
    ];
    
    protected $casts = [
        'violated_at' => 'datetime:immutable',
    ];
    
    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function rule()
    {
        return $this->belongsTo(SodRule::class, 'rule_id');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
    
    // SodViolationInterface implementation
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    public function getRuleId(): string
    {
        return $this->rule_id;
    }
    
    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }
    
    public function getTransactionType(): string
    {
        return $this->transaction_type;
    }
    
    public function getCreatorId(): string
    {
        return $this->creator_id;
    }
    
    public function getApproverId(): string
    {
        return $this->approver_id;
    }
    
    public function getViolatedAt(): \DateTimeImmutable
    {
        return $this->violated_at;
    }
}
```

#### 3. Repository Implementations

**ComplianceSchemeRepository:**

```php
<?php

namespace App\Repositories\Compliance;

use App\Models\ComplianceScheme;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface;

final readonly class ComplianceSchemeRepository implements ComplianceSchemeRepositoryInterface
{
    public function findById(string $id): ?ComplianceSchemeInterface
    {
        return ComplianceScheme::find($id);
    }
    
    public function findByTenantAndName(
        string $tenantId,
        string $schemeName
    ): ?ComplianceSchemeInterface {
        return ComplianceScheme::where('tenant_id', $tenantId)
            ->where('scheme_name', $schemeName)
            ->first();
    }
    
    public function save(ComplianceSchemeInterface $scheme): void
    {
        if ($scheme instanceof ComplianceScheme) {
            $scheme->save();
        }
    }
    
    public function findActiveSchemesForTenant(string $tenantId): array
    {
        return ComplianceScheme::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->all();
    }
}
```

**SodRuleRepository:**

```php
<?php

namespace App\Repositories\Compliance;

use App\Models\SodRule;
use Nexus\Compliance\Contracts\SodRuleInterface;
use Nexus\Compliance\Contracts\SodRuleRepositoryInterface;

final readonly class SodRuleRepository implements SodRuleRepositoryInterface
{
    public function findById(string $id): ?SodRuleInterface
    {
        return SodRule::find($id);
    }
    
    public function findByTenantAndType(
        string $tenantId,
        string $transactionType
    ): array {
        return SodRule::where('tenant_id', $tenantId)
            ->where('transaction_type', $transactionType)
            ->where('is_active', true)
            ->get()
            ->all();
    }
    
    public function save(SodRuleInterface $rule): void
    {
        if ($rule instanceof SodRule) {
            $rule->save();
        }
    }
    
    public function findActiveRulesForTenant(string $tenantId): array
    {
        return SodRule::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->all();
    }
}
```

**SodViolationRepository:**

```php
<?php

namespace App\Repositories\Compliance;

use App\Models\SodViolation;
use Nexus\Compliance\Contracts\SodViolationInterface;
use Nexus\Compliance\Contracts\SodViolationRepositoryInterface;

final readonly class SodViolationRepository implements SodViolationRepositoryInterface
{
    public function save(SodViolationInterface $violation): void
    {
        if ($violation instanceof SodViolation) {
            $violation->save();
        }
    }
    
    public function findByTenantAndDateRange(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return SodViolation::where('tenant_id', $tenantId)
            ->whereBetween('violated_at', [$from, $to])
            ->with(['rule', 'creator', 'approver'])
            ->get()
            ->all();
    }
}
```

#### 4. Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Compliance\Contracts\ComplianceManagerInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface;
use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\Contracts\SodRuleRepositoryInterface;
use Nexus\Compliance\Contracts\SodViolationRepositoryInterface;
use Nexus\Compliance\Services\ComplianceManager;
use Nexus\Compliance\Services\ConfigurationAuditor;
use Nexus\Compliance\Services\SodManager;
use App\Repositories\Compliance\ComplianceSchemeRepository;
use App\Repositories\Compliance\SodRuleRepository;
use App\Repositories\Compliance\SodViolationRepository;

final class ComplianceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            ComplianceSchemeRepositoryInterface::class,
            ComplianceSchemeRepository::class
        );
        
        $this->app->singleton(
            SodRuleRepositoryInterface::class,
            SodRuleRepository::class
        );
        
        $this->app->singleton(
            SodViolationRepositoryInterface::class,
            SodViolationRepository::class
        );
        
        // Bind managers
        $this->app->singleton(
            ComplianceManagerInterface::class,
            ComplianceManager::class
        );
        
        $this->app->singleton(
            SodManagerInterface::class,
            SodManager::class
        );
        
        // Bind ConfigurationAuditor
        $this->app->singleton(ConfigurationAuditor::class);
    }
    
    public function boot(): void
    {
        // Register in config/app.php 'providers' array
    }
}
```

#### 5. Middleware for SOD Validation

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\Exceptions\SodViolationException;

final readonly class ValidateSodCompliance
{
    public function __construct(
        private SodManagerInterface $sodManager
    ) {}
    
    public function handle(Request $request, Closure $next, string $transactionType)
    {
        // Get transaction creator and approver from request
        $creatorId = $request->input('creator_id') ?? $request->user()->id;
        $approverId = $request->user()->id;
        $tenantId = $request->user()->tenant_id;
        
        try {
            $this->sodManager->validateTransaction(
                tenantId: $tenantId,
                transactionType: $transactionType,
                creatorId: $creatorId,
                approverId: $approverId
            );
        } catch (SodViolationException $e) {
            return response()->json([
                'error' => 'SOD Violation',
                'message' => $e->getMessage(),
            ], 403);
        }
        
        return $next($request);
    }
}
```

**Usage in routes:**

```php
Route::post('/invoices/{invoice}/approve', [InvoiceController::class, 'approve'])
    ->middleware('sod:invoice_approval');
```

---

## Symfony Integration

### Complete Symfony Setup

#### 1. Doctrine Entities

**ComplianceScheme Entity:**

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'compliance_schemes')]
#[ORM\UniqueConstraint(columns: ['tenant_id', 'scheme_name'])]
class ComplianceScheme implements ComplianceSchemeInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;
    
    #[ORM\Column(type: 'ulid')]
    private Ulid $tenantId;
    
    #[ORM\Column(type: 'string', length: 255)]
    private string $schemeName;
    
    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;
    
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $activatedAt = null;
    
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $configuration = null;
    
    public function __construct(Ulid $tenantId, string $schemeName)
    {
        $this->id = new Ulid();
        $this->tenantId = $tenantId;
        $this->schemeName = $schemeName;
    }
    
    public function getId(): string
    {
        return $this->id->toRfc4122();
    }
    
    public function getTenantId(): string
    {
        return $this->tenantId->toRfc4122();
    }
    
    public function getSchemeName(): string
    {
        return $this->schemeName;
    }
    
    public function isActive(): bool
    {
        return $this->isActive;
    }
    
    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }
    
    public function activate(array $configuration): void
    {
        $this->isActive = true;
        $this->activatedAt = new \DateTimeImmutable();
        $this->configuration = $configuration;
    }
    
    public function deactivate(): void
    {
        $this->isActive = false;
    }
}
```

#### 2. Doctrine Repositories

```php
<?php

namespace App\Repository;

use App\Entity\ComplianceScheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface;

final class ComplianceSchemeRepository extends ServiceEntityRepository implements ComplianceSchemeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComplianceScheme::class);
    }
    
    public function findById(string $id): ?ComplianceSchemeInterface
    {
        return $this->find($id);
    }
    
    public function findByTenantAndName(
        string $tenantId,
        string $schemeName
    ): ?ComplianceSchemeInterface {
        return $this->findOneBy([
            'tenantId' => $tenantId,
            'schemeName' => $schemeName,
        ]);
    }
    
    public function save(ComplianceSchemeInterface $scheme): void
    {
        $this->getEntityManager()->persist($scheme);
        $this->getEntityManager()->flush();
    }
    
    public function findActiveSchemesForTenant(string $tenantId): array
    {
        return $this->findBy([
            'tenantId' => $tenantId,
            'isActive' => true,
        ]);
    }
}
```

#### 3. Service Configuration (services.yaml)

```yaml
services:
    # Repositories
    Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface:
        class: App\Repository\ComplianceSchemeRepository
        
    Nexus\Compliance\Contracts\SodRuleRepositoryInterface:
        class: App\Repository\SodRuleRepository
        
    Nexus\Compliance\Contracts\SodViolationRepositoryInterface:
        class: App\Repository\SodViolationRepository
    
    # Services
    Nexus\Compliance\Contracts\ComplianceManagerInterface:
        class: Nexus\Compliance\Services\ComplianceManager
        
    Nexus\Compliance\Contracts\SodManagerInterface:
        class: Nexus\Compliance\Services\SodManager
        
    Nexus\Compliance\Services\ConfigurationAuditor: ~
```

---

## Multi-Tenant Integration

### Tenant Context Integration

```php
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Compliance\Contracts\ComplianceManagerInterface;

final readonly class ComplianceController
{
    public function __construct(
        private TenantContextInterface $tenantContext,
        private ComplianceManagerInterface $complianceManager
    ) {}
    
    public function activateScheme(Request $request): Response
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        $schemeId = $this->complianceManager->activateScheme(
            tenantId: $tenantId,
            schemeName: $request->input('scheme_name'),
            configuration: $request->input('configuration', [])
        );
        
        return response()->json(['scheme_id' => $schemeId]);
    }
}
```

---

## Event-Driven Architecture

### Laravel Events

```php
<?php

namespace App\Events\Compliance;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SodViolationDetected
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public readonly string $tenantId,
        public readonly string $violationId,
        public readonly string $transactionType,
        public readonly string $creatorId,
        public readonly string $approverId
    ) {}
}
```

**Event Listener:**

```php
<?php

namespace App\Listeners\Compliance;

use App\Events\Compliance\SodViolationDetected;
use Nexus\Notifier\Contracts\NotificationManagerInterface;

final readonly class NotifyComplianceOfficer
{
    public function __construct(
        private NotificationManagerInterface $notifier
    ) {}
    
    public function handle(SodViolationDetected $event): void
    {
        $this->notifier->send(
            recipient: 'compliance-officer@company.com',
            channel: 'email',
            template: 'compliance.sod_violation',
            data: [
                'transaction_type' => $event->transactionType,
                'creator_id' => $event->creatorId,
                'approver_id' => $event->approverId,
            ]
        );
    }
}
```

---

## Testing Integration

### PHPUnit Tests (Laravel)

```php
<?php

namespace Tests\Feature\Compliance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use Nexus\Compliance\Contracts\ComplianceManagerInterface;
use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\ValueObjects\SeverityLevel;

final class ComplianceIntegrationTest extends TestCase
{
    private ComplianceManagerInterface $complianceManager;
    private SodManagerInterface $sodManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->complianceManager = app(ComplianceManagerInterface::class);
        $this->sodManager = app(SodManagerInterface::class);
    }
    
    public function test_activate_iso14001_scheme(): void
    {
        $tenant = Tenant::factory()->create();
        
        $schemeId = $this->complianceManager->activateScheme(
            tenantId: $tenant->id,
            schemeName: 'ISO14001',
            configuration: ['audit_frequency' => 'quarterly']
        );
        
        $this->assertNotEmpty($schemeId);
        $this->assertTrue(
            $this->complianceManager->isSchemeActive($tenant->id, 'ISO14001')
        );
    }
    
    public function test_sod_violation_prevents_approval(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Create SOD rule
        $this->sodManager->createRule(
            tenantId: $tenant->id,
            ruleName: 'Invoice Approval',
            transactionType: 'invoice_approval',
            severityLevel: SeverityLevel::CRITICAL,
            creatorRole: 'accountant',
            approverRole: 'manager'
        );
        
        // Attempt violation (same user)
        $this->expectException(\Nexus\Compliance\Exceptions\SodViolationException::class);
        
        $this->sodManager->validateTransaction(
            tenantId: $tenant->id,
            transactionType: 'invoice_approval',
            creatorId: $user->id,
            approverId: $user->id
        );
    }
}
```

---

## Best Practices

1. **Always validate SOD before approvals**
2. **Use middleware for automatic SOD validation**
3. **Log all compliance scheme activations**
4. **Monitor SOD violations with alerts**
5. **Implement tenant isolation at database level**
6. **Cache active schemes for performance**
7. **Use events for compliance notifications**

---

## See Also

- **[Getting Started Guide](getting-started.md)**
- **[API Reference](api-reference.md)**
- **[Basic Examples](examples/basic-usage.php)**
- **[Advanced Examples](examples/advanced-usage.php)**
