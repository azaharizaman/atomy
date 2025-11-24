# Integration Guide: Nexus Statutory

This guide demonstrates how to integrate the Nexus Statutory package into Laravel and Symfony applications.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Common Integration Patterns](#common-integration-patterns)
4. [Testing](#testing)
5. [Performance Optimization](#performance-optimization)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/statutory:"*@dev"
```

### Step 2: Create Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('report_type', 100);
            $table->string('format', 50);
            $table->json('content');
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 50);
            $table->timestamps();
            
            $table->index(['tenant_id', 'report_type']);
            $table->index(['tenant_id', 'generated_at']);
        });

        Schema::create('taxonomy_mappings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('gl_account_id', 26);
            $table->string('taxonomy_code', 100);
            $table->string('schema_id', 100);
            $table->string('schema_version', 50);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'gl_account_id']);
            $table->index(['taxonomy_code', 'schema_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomy_mappings');
        Schema::dropIfExists('statutory_reports');
    }
};
```

### Step 3: Create Eloquent Models

**StatutoryReport Model:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Statutory\Contracts\StatutoryReportInterface;

class StatutoryReport extends Model implements StatutoryReportInterface
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'report_type',
        'format',
        'content',
        'metadata',
        'generated_at',
        'submitted_at',
        'status',
    ];

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
        'generated_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // Implement StatutoryReportInterface methods

    public function getId(): string
    {
        return $this->id;
    }

    public function getReportType(): string
    {
        return $this->report_type;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->generated_at);
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submitted_at 
            ? \DateTimeImmutable::createFromMutable($this->submitted_at) 
            : null;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    // Scopes for multi-tenancy
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound(\Nexus\Tenant\Contracts\TenantContextInterface::class)) {
                $tenantContext = app(\Nexus\Tenant\Contracts\TenantContextInterface::class);
                $query->where('tenant_id', $tenantContext->getCurrentTenantId());
            }
        });

        static::creating(function ($report) {
            if (app()->bound(\Nexus\Tenant\Contracts\TenantContextInterface::class)) {
                $tenantContext = app(\Nexus\Tenant\Contracts\TenantContextInterface::class);
                $report->tenant_id = $tenantContext->getCurrentTenantId();
            }
        });
    }
}
```

### Step 4: Create Repository Implementation

```php
<?php

namespace App\Repositories;

use App\Models\StatutoryReport;
use Nexus\Statutory\Contracts\StatutoryReportInterface;
use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Exceptions\ReportNotFoundException;

final readonly class EloquentStatutoryReportRepository implements StatutoryReportRepositoryInterface
{
    public function save(StatutoryReportInterface $report): void
    {
        if ($report instanceof StatutoryReport) {
            $report->save();
            return;
        }

        // Handle DTO to Eloquent conversion if needed
        StatutoryReport::create([
            'report_type' => $report->getReportType(),
            'format' => $report->getFormat(),
            'content' => $report->getContent(),
            'metadata' => $report->getMetadata(),
            'generated_at' => $report->getGeneratedAt(),
            'submitted_at' => $report->getSubmittedAt(),
            'status' => $report->getStatus(),
        ]);
    }

    public function findById(string $id): StatutoryReportInterface
    {
        $report = StatutoryReport::find($id);

        if (!$report) {
            throw ReportNotFoundException::withId($id);
        }

        return $report;
    }

    public function findByType(string $reportType): array
    {
        return StatutoryReport::where('report_type', $reportType)
            ->orderBy('generated_at', 'desc')
            ->get()
            ->all();
    }
}
```

### Step 5: Create Accounting Adapter

```php
<?php

namespace App\Services\Statutory;

use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Contracts\ReportMetadataInterface;
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;

final readonly class LaravelAccountingAdapter implements TaxonomyReportGeneratorInterface
{
    public function __construct(
        private GeneralLedgerManagerInterface $glManager,
        private ReportMetadataInterface $metadataProvider,
    ) {}

    public function generateReport(
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $options = []
    ): array {
        // Extract GL data
        $accounts = $this->glManager->getChartOfAccounts();
        $balances = $this->glManager->getTrialBalance($startDate, $endDate);

        // Map GL accounts to taxonomy codes
        $taxonomyData = [];

        foreach ($balances as $accountId => $balance) {
            $account = $accounts[$accountId] ?? null;
            if (!$account) {
                continue;
            }

            // Get taxonomy mapping from database
            $mapping = \DB::table('taxonomy_mappings')
                ->where('gl_account_id', $accountId)
                ->where('effective_from', '<=', $endDate)
                ->where(function ($query) use ($endDate) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $endDate);
                })
                ->first();

            if ($mapping) {
                $taxonomyData[$mapping->taxonomy_code] = [
                    'value' => $balance,
                    'account_name' => $account['name'],
                    'account_code' => $account['code'],
                ];
            }
        }

        return $taxonomyData;
    }

    public function getReportMetadata(string $reportType): array
    {
        return $this->metadataProvider->getMetadata($reportType);
    }
}
```

### Step 6: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Contracts\PayrollStatutoryInterface;
use Nexus\Statutory\Contracts\ReportMetadataInterface;
use Nexus\Statutory\Services\StatutoryReportManager;
use Nexus\Statutory\Adapters\DefaultAccountingAdapter;
use Nexus\Statutory\Adapters\DefaultPayrollStatutoryAdapter;
use App\Repositories\EloquentStatutoryReportRepository;
use App\Services\Statutory\LaravelAccountingAdapter;
use App\Services\Statutory\LaravelReportMetadata;

class StatutoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            StatutoryReportRepositoryInterface::class,
            EloquentStatutoryReportRepository::class
        );

        // Bind metadata provider
        $this->app->singleton(
            ReportMetadataInterface::class,
            LaravelReportMetadata::class
        );

        // Bind accounting adapter (conditional on tenant country)
        $this->app->bind(
            TaxonomyReportGeneratorInterface::class,
            function ($app) {
                $tenantContext = $app->make(\Nexus\Tenant\Contracts\TenantContextInterface::class);
                $tenant = $tenantContext->getCurrentTenant();

                // Country-specific adapter binding
                return match ($tenant->getCountryCode()) {
                    'MY' => $app->make(\App\Services\Statutory\MalaysianAccountingAdapter::class),
                    'SG' => $app->make(\App\Services\Statutory\SingaporeanAccountingAdapter::class),
                    default => $app->make(DefaultAccountingAdapter::class),
                };
            }
        );

        // Bind payroll statutory (conditional on tenant country)
        $this->app->bind(
            PayrollStatutoryInterface::class,
            function ($app) {
                $tenantContext = $app->make(\Nexus\Tenant\Contracts\TenantContextInterface::class);
                $tenant = $tenantContext->getCurrentTenant();

                return match ($tenant->getCountryCode()) {
                    'MY' => $app->make(\Nexus\PayrollMysStatutory\MalaysianStatutoryCalculator::class),
                    default => $app->make(DefaultPayrollStatutoryAdapter::class),
                };
            }
        );

        // Bind main service
        $this->app->singleton(StatutoryReportManager::class);
    }
}
```

### Step 7: Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\Statutory\Services\StatutoryReportManager;
use Nexus\Statutory\Enums\ReportFormat;

class StatutoryReportController extends Controller
{
    public function __construct(
        private readonly StatutoryReportManager $reportManager
    ) {}

    public function generateFinancialStatement(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'format' => 'required|in:JSON,XBRL,PDF,CSV',
        ]);

        $report = $this->reportManager->generateReport(
            reportType: $validated['report_type'],
            startDate: new \DateTimeImmutable($validated['start_date']),
            endDate: new \DateTimeImmutable($validated['end_date']),
            format: ReportFormat::from($validated['format']),
            options: []
        );

        return response()->json([
            'report_id' => $report->getId(),
            'status' => $report->getStatus(),
            'generated_at' => $report->getGeneratedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function downloadReport(string $reportId, Request $request)
    {
        $format = $request->input('format', 'PDF');

        $report = $this->reportManager->getReport($reportId);

        $content = $report->getContent();

        return match ($format) {
            'PDF' => response()->streamDownload(
                fn() => print($content['pdf']),
                "report-{$reportId}.pdf",
                ['Content-Type' => 'application/pdf']
            ),
            'CSV' => response()->streamDownload(
                fn() => print($content['csv']),
                "report-{$reportId}.csv",
                ['Content-Type' => 'text/csv']
            ),
            'XBRL' => response()->streamDownload(
                fn() => print($content['xbrl']),
                "report-{$reportId}.xml",
                ['Content-Type' => 'application/xml']
            ),
            default => response()->json($content),
        };
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/statutory:"*@dev"
```

### Step 2: Create Doctrine Entities

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Statutory\Contracts\StatutoryReportInterface;

#[ORM\Entity]
#[ORM\Table(name: 'statutory_reports')]
#[ORM\Index(columns: ['tenant_id', 'report_type'])]
class StatutoryReport implements StatutoryReportInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $reportType;

    #[ORM\Column(type: 'string', length: 50)]
    private string $format;

    #[ORM\Column(type: 'json')]
    private array $content;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $generatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    public function __construct(
        string $id,
        string $tenantId,
        string $reportType,
        string $format,
        array $content,
        \DateTimeImmutable $generatedAt,
        string $status,
        ?array $metadata = null,
        ?\DateTimeImmutable $submittedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->reportType = $reportType;
        $this->format = $format;
        $this->content = $content;
        $this->generatedAt = $generatedAt;
        $this->status = $status;
        $this->metadata = $metadata;
        $this->submittedAt = $submittedAt;
    }

    // Implement StatutoryReportInterface methods
    public function getId(): string { return $this->id; }
    public function getReportType(): string { return $this->reportType; }
    public function getFormat(): string { return $this->format; }
    public function getContent(): array { return $this->content; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getGeneratedAt(): \DateTimeImmutable { return $this->generatedAt; }
    public function getSubmittedAt(): ?\DateTimeImmutable { return $this->submittedAt; }
    public function getStatus(): string { return $this->status; }
}
```

### Step 3: Create Repository

```php
<?php

namespace App\Repository;

use App\Entity\StatutoryReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Statutory\Contracts\StatutoryReportInterface;
use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Exceptions\ReportNotFoundException;

class StatutoryReportRepository extends ServiceEntityRepository implements StatutoryReportRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatutoryReport::class);
    }

    public function save(StatutoryReportInterface $report): void
    {
        $this->getEntityManager()->persist($report);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): StatutoryReportInterface
    {
        $report = $this->find($id);

        if (!$report) {
            throw ReportNotFoundException::withId($id);
        }

        return $report;
    }

    public function findByType(string $reportType): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.reportType = :reportType')
            ->setParameter('reportType', $reportType)
            ->orderBy('r.generatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

### Step 4: Configure Services (config/services.yaml)

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Repository
    Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface:
        class: App\Repository\StatutoryReportRepository

    # Metadata provider
    Nexus\Statutory\Contracts\ReportMetadataInterface:
        class: App\Service\Statutory\SymfonyReportMetadata

    # Accounting adapter (conditional binding via factory)
    Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface:
        factory: ['@App\Factory\AccountingAdapterFactory', 'create']

    # Payroll statutory (conditional binding via factory)
    Nexus\Statutory\Contracts\PayrollStatutoryInterface:
        factory: ['@App\Factory\PayrollStatutoryFactory', 'create']

    # Main service
    Nexus\Statutory\Services\StatutoryReportManager:
        public: true
```

### Step 5: Create Factory for Conditional Binding

```php
<?php

namespace App\Factory;

use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Adapters\DefaultAccountingAdapter;
use Nexus\Tenant\Contracts\TenantContextInterface;
use App\Service\Statutory\MalaysianAccountingAdapter;
use Psr\Container\ContainerInterface;

final readonly class AccountingAdapterFactory
{
    public function __construct(
        private ContainerInterface $container,
        private TenantContextInterface $tenantContext
    ) {}

    public function create(): TaxonomyReportGeneratorInterface
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        return match ($tenant->getCountryCode()) {
            'MY' => $this->container->get(MalaysianAccountingAdapter::class),
            'SG' => $this->container->get(SingaporeanAccountingAdapter::class),
            default => $this->container->get(DefaultAccountingAdapter::class),
        };
    }
}
```

---

## Common Integration Patterns

### 1. Dependency Injection (Always Inject Interfaces)

```php
// ✅ CORRECT
public function __construct(
    private readonly StatutoryReportManager $reportManager,
    private readonly TaxonomyReportGeneratorInterface $accountingAdapter,
    private readonly PayrollStatutoryInterface $payrollStatutory
) {}

// ❌ WRONG - Don't inject concrete classes from other packages
public function __construct(
    private readonly DefaultAccountingAdapter $adapter // Violates interface contract
) {}
```

### 2. Multi-Tenancy (Always Scope by Tenant)

```php
// ✅ CORRECT - Repository auto-scopes via global scope
$reports = $this->reportRepository->findByType('balance_sheet');

// ❌ WRONG - Querying without tenant context
$reports = DB::table('statutory_reports')
    ->where('report_type', 'balance_sheet')
    ->get(); // Returns ALL tenants' data!
```

### 3. Exception Handling

```php
use Nexus\Statutory\Exceptions\ValidationException;
use Nexus\Statutory\Exceptions\DataExtractionException;
use Nexus\Statutory\Exceptions\ReportNotFoundException;

try {
    $report = $this->reportManager->generateReport(
        reportType: 'profit_loss',
        startDate: new \DateTimeImmutable('2024-01-01'),
        endDate: new \DateTimeImmutable('2024-12-31'),
        format: ReportFormat::XBRL,
        options: ['schema_id' => 'MY-GAAP-2024']
    );
} catch (ValidationException $e) {
    // Schema validation failed
    Log::error('XBRL validation failed', ['errors' => $e->getValidationErrors()]);
    throw new \RuntimeException('Invalid report data');
} catch (DataExtractionException $e) {
    // GL data extraction failed
    Log::error('Failed to extract GL data', ['error' => $e->getMessage()]);
    throw new \RuntimeException('Data extraction error');
} catch (ReportNotFoundException $e) {
    // Report not found
    return response()->json(['error' => 'Report not found'], 404);
}
```

### 4. Event-Driven Architecture

```php
// Listen for report lifecycle events
use Nexus\Statutory\Events\ReportGeneratedEvent;
use Nexus\Statutory\Events\ReportSubmittedEvent;
use Nexus\Statutory\Events\ReportValidatedEvent;

// Laravel Event Listener
namespace App\Listeners;

class NotifyAccountantOnReportGenerated
{
    public function __construct(
        private readonly \Nexus\Notifier\Contracts\NotificationManagerInterface $notifier
    ) {}

    public function handle(ReportGeneratedEvent $event): void
    {
        $report = $event->getReport();

        $this->notifier->send(
            recipient: 'accountant@company.com',
            channel: 'email',
            template: 'statutory.report_generated',
            data: [
                'report_id' => $report->getId(),
                'report_type' => $report->getReportType(),
                'generated_at' => $report->getGeneratedAt()->format('Y-m-d H:i:s'),
            ]
        );
    }
}
```

---

## Testing

### Unit Tests (Mocking Dependencies)

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nexus\Statutory\Services\StatutoryReportManager;
use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Contracts\PayrollStatutoryInterface;
use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Enums\ReportFormat;

class StatutoryReportManagerTest extends TestCase
{
    public function test_generates_report_successfully(): void
    {
        // Mock dependencies
        $accountingAdapter = $this->createMock(TaxonomyReportGeneratorInterface::class);
        $accountingAdapter->method('generateReport')
            ->willReturn(['1000' => ['value' => 100000, 'name' => 'Revenue']]);

        $repository = $this->createMock(StatutoryReportRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save');

        $manager = new StatutoryReportManager(
            accountingAdapter: $accountingAdapter,
            payrollStatutory: $this->createMock(PayrollStatutoryInterface::class),
            repository: $repository
        );

        $report = $manager->generateReport(
            reportType: 'profit_loss',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-12-31'),
            format: ReportFormat::JSON,
            options: []
        );

        $this->assertNotNull($report->getId());
        $this->assertEquals('profit_loss', $report->getReportType());
    }
}
```

### Integration Tests (End-to-End)

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Statutory\Services\StatutoryReportManager;
use Nexus\Statutory\Enums\ReportFormat;

class StatutoryReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_xbrl_report_for_malaysian_tenant(): void
    {
        // Arrange: Set up tenant context
        $this->actingAsTenant('MY');

        // Arrange: Create taxonomy mappings
        \DB::table('taxonomy_mappings')->insert([
            'id' => \Str::ulid(),
            'tenant_id' => 'MY-TENANT-001',
            'gl_account_id' => 'GL-1000',
            'taxonomy_code' => 'Revenue',
            'schema_id' => 'MY-GAAP-2024',
            'schema_version' => '1.0',
            'effective_from' => '2024-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: Generate report
        $manager = app(StatutoryReportManager::class);
        $report = $manager->generateReport(
            reportType: 'profit_loss',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-12-31'),
            format: ReportFormat::XBRL,
            options: ['schema_id' => 'MY-GAAP-2024']
        );

        // Assert
        $this->assertDatabaseHas('statutory_reports', [
            'id' => $report->getId(),
            'tenant_id' => 'MY-TENANT-001',
            'report_type' => 'profit_loss',
            'format' => 'XBRL',
        ]);

        $content = $report->getContent();
        $this->assertArrayHasKey('xbrl', $content);
        $this->assertStringContainsString('<xbrl', $content['xbrl']);
    }
}
```

---

## Performance Optimization

### 1. Database Indexing

```sql
-- Composite indexes for common queries
CREATE INDEX idx_tenant_report_type ON statutory_reports(tenant_id, report_type);
CREATE INDEX idx_tenant_generated_at ON statutory_reports(tenant_id, generated_at);
CREATE INDEX idx_taxonomy_mapping ON taxonomy_mappings(tenant_id, gl_account_id, effective_from);
```

### 2. Caching Taxonomy Mappings

```php
use Illuminate\Support\Facades\Cache;

class CachedTaxonomyMappingRepository
{
    public function getMappings(string $tenantId, \DateTimeImmutable $date): array
    {
        $cacheKey = "taxonomy_mappings:{$tenantId}:{$date->format('Y-m-d')}";

        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $date) {
            return \DB::table('taxonomy_mappings')
                ->where('tenant_id', $tenantId)
                ->where('effective_from', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $date);
                })
                ->get()
                ->keyBy('gl_account_id')
                ->toArray();
        });
    }
}
```

### 3. Batch Processing Large Reports

```php
public function generateLargeReport(string $reportType): void
{
    // Process in chunks to avoid memory issues
    $glAccounts = $this->glManager->getChartOfAccounts();

    $taxonomyData = [];
    foreach (array_chunk($glAccounts, 100) as $chunk) {
        foreach ($chunk as $account) {
            // Process each account
            $taxonomyData[$account['code']] = $this->extractAccountData($account);
        }

        // Clear memory periodically
        gc_collect_cycles();
    }

    // Generate report
    $this->reportManager->generateReport(/* ... */);
}
```

---

**Last Updated:** November 24, 2025  
**Maintained By:** Nexus Statutory Package Team
