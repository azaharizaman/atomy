# Getting Started with Nexus\Compliance

This guide will help you quickly integrate the Nexus\Compliance package into your application.

## Prerequisites

- PHP 8.3 or higher
- Composer
- A PHP framework (Laravel, Symfony, or any PSR-compatible framework)

## Installation

Install the package via Composer:

```bash
composer require nexus/compliance:"*@dev"
```

## Basic Configuration

### Step 1: Implement Repository Interfaces

The Compliance package defines repository interfaces that you must implement in your application layer.

#### Laravel Example

```php
namespace App\Repositories\Compliance;

use App\Models\ComplianceScheme as ComplianceSchemeModel;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface;

final readonly class ComplianceSchemeRepository implements ComplianceSchemeRepositoryInterface
{
    public function findById(string $id): ?ComplianceSchemeInterface
    {
        return ComplianceSchemeModel::find($id);
    }
    
    public function findByTenantAndName(string $tenantId, string $schemeName): ?ComplianceSchemeInterface
    {
        return ComplianceSchemeModel::where('tenant_id', $tenantId)
            ->where('scheme_name', $schemeName)
            ->first();
    }
    
    public function save(ComplianceSchemeInterface $scheme): void
    {
        if ($scheme instanceof ComplianceSchemeModel) {
            $scheme->save();
        }
    }
    
    public function findActiveSchemesForTenant(string $tenantId): array
    {
        return ComplianceSchemeModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->all();
    }
}
```

### Step 2: Implement Entity Interfaces

Create Eloquent models that implement the package's entity interfaces.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;

final class ComplianceScheme extends Model implements ComplianceSchemeInterface
{
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
    
    public function getId(): string
    {
        return (string) $this->id;
    }
    
    public function getTenantId(): string
    {
        return (string) $this->tenant_id;
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

### Step 3: Create Database Migrations

```php
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
        });
        
        Schema::create('sod_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('rule_name');
            $table->string('transaction_type');
            $table->string('severity_level');
            $table->string('creator_role');
            $table->string('approver_role');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'transaction_type']);
            $table->index(['tenant_id', 'is_active']);
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
            
            $table->foreign('rule_id')->references('id')->on('sod_rules');
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

### Step 4: Bind Interfaces in Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Compliance\Contracts\ComplianceManagerInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface;
use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\Contracts\SodRuleRepositoryInterface;
use Nexus\Compliance\Contracts\SodViolationRepositoryInterface;
use Nexus\Compliance\Services\ComplianceManager;
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
    }
}
```

## Your First Integration

### Example 1: Activate ISO 14001 Compliance Scheme

```php
use Nexus\Compliance\Contracts\ComplianceManagerInterface;

// Inject via constructor
public function __construct(
    private readonly ComplianceManagerInterface $complianceManager
) {}

public function enableEnvironmentalCompliance(): void
{
    $schemeId = $this->complianceManager->activateScheme(
        tenantId: auth()->user()->tenant_id,
        schemeName: 'ISO14001',
        configuration: [
            'audit_frequency' => 'quarterly',
            'enable_environmental_tracking' => true,
            'carbon_reporting' => true,
        ]
    );
    
    // Scheme activated successfully
    logger()->info("ISO 14001 compliance scheme activated", ['scheme_id' => $schemeId]);
}
```

### Example 2: Create SOD Rule

```php
use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\ValueObjects\SeverityLevel;

public function __construct(
    private readonly SodManagerInterface $sodManager
) {}

public function createInvoiceApprovalRule(): void
{
    $ruleId = $this->sodManager->createRule(
        tenantId: auth()->user()->tenant_id,
        ruleName: 'Invoice Creator Cannot Approve',
        transactionType: 'invoice_approval',
        severityLevel: SeverityLevel::CRITICAL,
        creatorRole: 'accountant',
        approverRole: 'manager'
    );
    
    logger()->info("SOD rule created", ['rule_id' => $ruleId]);
}
```

### Example 3: Validate Transaction for SOD Violation

```php
use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\Exceptions\SodViolationException;

public function approveInvoice(string $invoiceId, string $approverId): void
{
    $invoice = Invoice::findOrFail($invoiceId);
    
    // Check for SOD violation
    try {
        $this->sodManager->validateTransaction(
            tenantId: $invoice->tenant_id,
            transactionType: 'invoice_approval',
            creatorId: $invoice->created_by,
            approverId: $approverId
        );
    } catch (SodViolationException $e) {
        // Log violation and prevent approval
        logger()->warning("SOD violation detected", [
            'invoice_id' => $invoiceId,
            'violation' => $e->getMessage(),
        ]);
        
        throw $e; // Prevent transaction
    }
    
    // Proceed with approval
    $invoice->approve($approverId);
}
```

## Common Use Cases

### Use Case 1: Multi-Tenant Compliance Isolation

```php
// Each tenant has independent compliance schemes
$complianceManager->activateScheme('tenant-a', 'ISO14001', [...]);
$complianceManager->activateScheme('tenant-b', 'SOX', [...]);

// Tenant A's schemes don't affect Tenant B
$tenantASchemes = $complianceManager->getActiveSchemes('tenant-a'); // ['ISO14001']
$tenantBSchemes = $complianceManager->getActiveSchemes('tenant-b'); // ['SOX']
```

### Use Case 2: Multiple Compliance Schemes

```php
// Activate both ISO 14001 and SOX for a tenant
$complianceManager->activateScheme('tenant-123', 'ISO14001', [...]);
$complianceManager->activateScheme('tenant-123', 'SOX', [...]);

// Both schemes are now active
$activeSchemes = $complianceManager->getActiveSchemes('tenant-123');
// Returns: ['ISO14001', 'SOX']
```

### Use Case 3: Periodic SOD Violation Reports

```php
public function generateMonthlyViolationReport(): array
{
    $from = now()->startOfMonth();
    $to = now()->endOfMonth();
    
    $violations = $this->sodManager->getViolations(
        tenantId: auth()->user()->tenant_id,
        from: $from,
        to: $to
    );
    
    return [
        'period' => $from->format('F Y'),
        'total_violations' => count($violations),
        'violations' => $violations,
    ];
}
```

## Next Steps

- **[API Reference](api-reference.md)** - Explore all available interfaces and methods
- **[Integration Guide](integration-guide.md)** - Learn advanced integration patterns
- **[Basic Examples](examples/basic-usage.php)** - See working code examples
- **[Advanced Examples](examples/advanced-usage.php)** - Complex scenarios

## Troubleshooting

### Issue: "Interface not bound"

**Error:** `Target interface [Nexus\Compliance\Contracts\ComplianceManagerInterface] is not instantiable.`

**Solution:** Ensure you've registered the `ComplianceServiceProvider` in your application's `config/app.php` or `bootstrap/providers.php`.

### Issue: "Class not found"

**Error:** `Class "Nexus\Compliance\Services\ComplianceManager" not found`

**Solution:** Run `composer dump-autoload` to regenerate autoload files.

### Issue: "Column not found"

**Error:** `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'tenant_id'`

**Solution:** Run your database migrations: `php artisan migrate`

## Support

For additional help, refer to the [main documentation](../README.md) or consult the [Integration Guide](integration-guide.md).
