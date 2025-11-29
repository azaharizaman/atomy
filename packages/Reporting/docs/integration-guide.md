# Integration Guide: Reporting

This guide shows how to integrate the Nexus Reporting package into Laravel and Symfony applications.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Common Patterns](#common-patterns)
4. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/reporting:"*@dev"
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
        // Report Definitions
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('name');
            $table->string('query_id', 26)->index();
            $table->string('template_id', 26)->nullable();
            $table->string('format', 10); // pdf, excel, csv, json, html
            $table->json('parameters')->nullable();
            $table->json('schedule')->nullable();
            $table->string('retention_tier', 20)->default('active');
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 26);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'is_active']);
        });
        
        // Generated Reports
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('definition_id', 26)->index();
            $table->string('tenant_id', 26)->index();
            $table->string('format', 10);
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('duration_ms');
            $table->string('retention_tier', 20)->default('active');
            $table->string('query_result_id', 26)->nullable();
            $table->boolean('is_successful')->default(true);
            $table->text('error')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            
            $table->foreign('definition_id')
                  ->references('id')
                  ->on('report_definitions')
                  ->onDelete('cascade');
        });
        
        // Report Distributions
        Schema::create('report_distributions', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('report_id', 26)->index();
            $table->string('tenant_id', 26)->index();
            $table->string('channel', 20); // email, slack, webhook, in_app
            $table->json('recipients');
            $table->json('notification_ids')->nullable();
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('distributed_at');
            $table->timestamps();
            
            $table->foreign('report_id')
                  ->references('id')
                  ->on('generated_reports')
                  ->onDelete('cascade');
        });
        
        // Report Schedules
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('definition_id', 26)->index();
            $table->string('tenant_id', 26)->index();
            $table->string('scheduler_job_id', 26)->nullable();
            $table->string('type', 20); // once, daily, weekly, monthly, yearly, cron
            $table->string('cron_expression')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('occurrence_count')->default(0);
            $table->json('distribution_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('definition_id')
                  ->references('id')
                  ->on('report_definitions')
                  ->onDelete('cascade');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('report_distributions');
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('report_definitions');
    }
};
```

### Step 3: Create Eloquent Models

**ReportDefinition Model:**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportSchedule;
use Nexus\Reporting\ValueObjects\RetentionTier;

class ReportDefinition extends Model implements ReportDefinitionInterface
{
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'query_id',
        'template_id',
        'format',
        'parameters',
        'schedule',
        'retention_tier',
        'is_active',
        'created_by',
        'archived_at',
    ];
    
    protected $casts = [
        'parameters' => 'array',
        'schedule' => 'array',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    public function getQueryId(): string
    {
        return $this->query_id;
    }
    
    public function getTemplateId(): ?string
    {
        return $this->template_id;
    }
    
    public function getFormat(): ReportFormat
    {
        return ReportFormat::from($this->format);
    }
    
    public function getParameters(): array
    {
        return $this->parameters ?? [];
    }
    
    public function getSchedule(): ?ReportSchedule
    {
        if (empty($this->schedule)) {
            return null;
        }
        
        return ReportSchedule::fromArray($this->schedule);
    }
    
    public function getRetentionTier(): RetentionTier
    {
        return RetentionTier::from($this->retention_tier);
    }
    
    public function isActive(): bool
    {
        return $this->is_active && $this->archived_at === null;
    }
    
    public function getCreatedBy(): string
    {
        return $this->created_by;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->created_at);
    }
}
```

### Step 4: Create Repository Implementation

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use Nexus\Tenant\Contracts\TenantContextInterface;
use App\Models\ReportDefinition;
use App\Models\GeneratedReport;

final readonly class EloquentReportRepository implements ReportRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}
    
    public function findById(string $id): ReportDefinitionInterface
    {
        $report = ReportDefinition::query()
            ->where('id', $id)
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->first();
        
        if (!$report) {
            throw ReportNotFoundException::forId($id);
        }
        
        return $report;
    }
    
    public function save(ReportDefinitionInterface $definition): void
    {
        $model = new ReportDefinition([
            'id' => $definition->getId(),
            'tenant_id' => $definition->getTenantId(),
            'name' => $definition->getName(),
            'query_id' => $definition->getQueryId(),
            'template_id' => $definition->getTemplateId(),
            'format' => $definition->getFormat()->value,
            'parameters' => $definition->getParameters(),
            'retention_tier' => $definition->getRetentionTier()->value,
            'created_by' => $definition->getCreatedBy(),
        ]);
        
        $model->save();
    }
    
    public function update(ReportDefinitionInterface $definition): void
    {
        $model = ReportDefinition::findOrFail($definition->getId());
        
        $model->update([
            'name' => $definition->getName(),
            'template_id' => $definition->getTemplateId(),
            'format' => $definition->getFormat()->value,
            'parameters' => $definition->getParameters(),
        ]);
    }
    
    public function archive(string $id): void
    {
        $model = ReportDefinition::query()
            ->where('id', $id)
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->first();
        
        if (!$model) {
            throw ReportNotFoundException::forId($id);
        }
        
        $model->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);
    }
    
    public function findGeneratedReportById(string $id): array
    {
        $report = GeneratedReport::query()
            ->where('id', $id)
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->first();
        
        if (!$report) {
            throw ReportNotFoundException::forGeneratedReport($id);
        }
        
        return $report->toArray();
    }
}
```

### Step 5: Create Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Contracts\ReportGeneratorInterface;
use Nexus\Reporting\Contracts\ReportDistributorInterface;
use Nexus\Reporting\Contracts\ReportRetentionInterface;
use Nexus\Reporting\Services\ReportManager;
use App\Repositories\EloquentReportRepository;
use App\Services\Reporting\ReportGenerator;
use App\Services\Reporting\ReportDistributor;
use App\Services\Reporting\ReportRetentionManager;

class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository binding
        $this->app->singleton(
            ReportRepositoryInterface::class,
            EloquentReportRepository::class
        );
        
        // Generator binding
        $this->app->singleton(
            ReportGeneratorInterface::class,
            ReportGenerator::class
        );
        
        // Distributor binding
        $this->app->singleton(
            ReportDistributorInterface::class,
            ReportDistributor::class
        );
        
        // Retention binding
        $this->app->singleton(
            ReportRetentionInterface::class,
            ReportRetentionManager::class
        );
        
        // Main manager
        $this->app->singleton(ReportManager::class);
    }
    
    public function provides(): array
    {
        return [
            ReportRepositoryInterface::class,
            ReportGeneratorInterface::class,
            ReportDistributorInterface::class,
            ReportRetentionInterface::class,
            ReportManager::class,
        ];
    }
}
```

### Step 6: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\ReportingServiceProvider::class,
],
```

Or for Laravel 11+, in `bootstrap/providers.php`:

```php
return [
    // ...
    App\Providers\ReportingServiceProvider::class,
];
```

### Step 7: Create Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Reporting\Services\ReportManager;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportSchedule;
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use Nexus\Reporting\Exceptions\ReportGenerationException;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportManager $reportManager
    ) {}
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'query_id' => 'required|string|size:26',
            'format' => 'required|in:pdf,excel,csv,json,html',
            'parameters' => 'array',
            'template_id' => 'nullable|string|size:26',
        ]);
        
        $definition = $this->reportManager->createReport(
            name: $validated['name'],
            queryId: $validated['query_id'],
            format: ReportFormat::from($validated['format']),
            parameters: $validated['parameters'] ?? [],
            templateId: $validated['template_id'] ?? null
        );
        
        return response()->json([
            'id' => $definition->getId(),
            'name' => $definition->getName(),
            'format' => $definition->getFormat()->value,
        ], 201);
    }
    
    public function generate(string $id, Request $request): JsonResponse
    {
        try {
            $result = $this->reportManager->generateReport(
                reportId: $id,
                parameterOverrides: $request->input('parameters', [])
            );
            
            return response()->json([
                'report_id' => $result->reportId,
                'file_path' => $result->filePath,
                'file_size' => $result->fileSize,
                'duration_ms' => $result->durationMs,
                'format' => $result->format->value,
            ]);
            
        } catch (ReportNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (ReportGenerationException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function download(string $id)
    {
        try {
            $result = $this->reportManager->generateReport($id);
            
            return response()->download(
                $result->filePath,
                "report.{$result->format->extension()}",
                ['Content-Type' => $result->format->mimeType()]
            );
            
        } catch (ReportNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
    
    public function schedule(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:once,daily,weekly,monthly,cron',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
            'cron_expression' => 'required_if:type,cron|string',
            'recipients' => 'array',
            'channel' => 'in:email,slack,webhook,in_app',
        ]);
        
        $schedule = match ($validated['type']) {
            'once' => ReportSchedule::once(new \DateTimeImmutable($validated['starts_at'])),
            'daily' => ReportSchedule::daily(
                new \DateTimeImmutable($validated['starts_at']),
                isset($validated['ends_at']) ? new \DateTimeImmutable($validated['ends_at']) : null
            ),
            'weekly' => ReportSchedule::weekly(
                new \DateTimeImmutable($validated['starts_at']),
                isset($validated['ends_at']) ? new \DateTimeImmutable($validated['ends_at']) : null
            ),
            'monthly' => ReportSchedule::monthly(
                new \DateTimeImmutable($validated['starts_at']),
                isset($validated['ends_at']) ? new \DateTimeImmutable($validated['ends_at']) : null
            ),
            'cron' => ReportSchedule::cron(
                $validated['cron_expression'],
                new \DateTimeImmutable($validated['starts_at']),
                isset($validated['ends_at']) ? new \DateTimeImmutable($validated['ends_at']) : null
            ),
        };
        
        $jobId = $this->reportManager->scheduleReport(
            reportId: $id,
            schedule: $schedule,
            recipients: $validated['recipients'] ?? [],
            channel: $validated['channel'] ?? 'email'
        );
        
        return response()->json([
            'job_id' => $jobId,
            'schedule_type' => $validated['type'],
        ]);
    }
}
```

### Step 8: Define Routes

```php
// routes/api.php

use App\Http\Controllers\ReportController;

Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::post('/', [ReportController::class, 'store']);
    Route::post('/{id}/generate', [ReportController::class, 'generate']);
    Route::get('/{id}/download', [ReportController::class, 'download']);
    Route::post('/{id}/schedule', [ReportController::class, 'schedule']);
});
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/reporting:"*@dev"
```

### Step 2: Create Doctrine Entity

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportSchedule;
use Nexus\Reporting\ValueObjects\RetentionTier;

#[ORM\Entity(repositoryClass: ReportDefinitionRepository::class)]
#[ORM\Table(name: 'report_definitions')]
class ReportDefinition implements ReportDefinitionInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;
    
    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;
    
    #[ORM\Column(type: 'string', length: 255)]
    private string $name;
    
    #[ORM\Column(type: 'string', length: 26)]
    private string $queryId;
    
    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $templateId = null;
    
    #[ORM\Column(type: 'string', length: 10)]
    private string $format;
    
    #[ORM\Column(type: 'json')]
    private array $parameters = [];
    
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $schedule = null;
    
    #[ORM\Column(type: 'string', length: 20)]
    private string $retentionTier = 'active';
    
    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;
    
    #[ORM\Column(type: 'string', length: 26)]
    private string $createdBy;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
    
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getTenantId(): string
    {
        return $this->tenantId;
    }
    
    public function getQueryId(): string
    {
        return $this->queryId;
    }
    
    public function getTemplateId(): ?string
    {
        return $this->templateId;
    }
    
    public function getFormat(): ReportFormat
    {
        return ReportFormat::from($this->format);
    }
    
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    public function getSchedule(): ?ReportSchedule
    {
        if (empty($this->schedule)) {
            return null;
        }
        
        return ReportSchedule::fromArray($this->schedule);
    }
    
    public function getRetentionTier(): RetentionTier
    {
        return RetentionTier::from($this->retentionTier);
    }
    
    public function isActive(): bool
    {
        return $this->isActive && $this->archivedAt === null;
    }
    
    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

### Step 3: Create Repository

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use Nexus\Tenant\Contracts\TenantContextInterface;
use App\Entity\ReportDefinition;
use App\Entity\GeneratedReport;

class ReportDefinitionRepository extends ServiceEntityRepository implements ReportRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly TenantContextInterface $tenantContext
    ) {
        parent::__construct($registry, ReportDefinition::class);
    }
    
    public function findById(string $id): ReportDefinitionInterface
    {
        $report = $this->createQueryBuilder('r')
            ->where('r.id = :id')
            ->andWhere('r.tenantId = :tenantId')
            ->setParameter('id', $id)
            ->setParameter('tenantId', $this->tenantContext->getCurrentTenantId())
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$report) {
            throw ReportNotFoundException::forId($id);
        }
        
        return $report;
    }
    
    public function save(ReportDefinitionInterface $definition): void
    {
        $em = $this->getEntityManager();
        $em->persist($definition);
        $em->flush();
    }
    
    public function update(ReportDefinitionInterface $definition): void
    {
        $em = $this->getEntityManager();
        $em->flush();
    }
    
    public function archive(string $id): void
    {
        $report = $this->findById($id);
        
        $em = $this->getEntityManager();
        $report->setIsActive(false);
        $report->setArchivedAt(new \DateTimeImmutable());
        $em->flush();
    }
    
    public function findGeneratedReportById(string $id): array
    {
        $em = $this->getEntityManager();
        $report = $em->getRepository(GeneratedReport::class)->find($id);
        
        if (!$report || $report->getTenantId() !== $this->tenantContext->getCurrentTenantId()) {
            throw ReportNotFoundException::forGeneratedReport($id);
        }
        
        return [
            'id' => $report->getId(),
            'file_path' => $report->getFilePath(),
            'format' => $report->getFormat(),
            'file_size' => $report->getFileSize(),
        ];
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

    # Repository binding
    Nexus\Reporting\Contracts\ReportRepositoryInterface:
        class: App\Repository\ReportDefinitionRepository
    
    # Generator binding
    Nexus\Reporting\Contracts\ReportGeneratorInterface:
        class: App\Service\Reporting\ReportGenerator
    
    # Distributor binding
    Nexus\Reporting\Contracts\ReportDistributorInterface:
        class: App\Service\Reporting\ReportDistributor
    
    # Retention binding
    Nexus\Reporting\Contracts\ReportRetentionInterface:
        class: App\Service\Reporting\ReportRetentionManager
    
    # Main Manager
    Nexus\Reporting\Services\ReportManager:
        arguments:
            $repository: '@Nexus\Reporting\Contracts\ReportRepositoryInterface'
            $generator: '@Nexus\Reporting\Contracts\ReportGeneratorInterface'
            $distributor: '@Nexus\Reporting\Contracts\ReportDistributorInterface'
            $retention: '@Nexus\Reporting\Contracts\ReportRetentionInterface'
            $tenantContext: '@Nexus\Tenant\Contracts\TenantContextInterface'
            $logger: '@logger'
```

### Step 5: Create Controller

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\Reporting\Services\ReportManager;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\Exceptions\ReportNotFoundException;

#[Route('/api/reports')]
class ReportController extends AbstractController
{
    public function __construct(
        private readonly ReportManager $reportManager
    ) {}
    
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $definition = $this->reportManager->createReport(
            name: $data['name'],
            queryId: $data['query_id'],
            format: ReportFormat::from($data['format']),
            parameters: $data['parameters'] ?? [],
            templateId: $data['template_id'] ?? null
        );
        
        return $this->json([
            'id' => $definition->getId(),
            'name' => $definition->getName(),
        ], Response::HTTP_CREATED);
    }
    
    #[Route('/{id}/generate', methods: ['POST'])]
    public function generate(string $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            
            $result = $this->reportManager->generateReport(
                reportId: $id,
                parameterOverrides: $data['parameters'] ?? []
            );
            
            return $this->json([
                'report_id' => $result->reportId,
                'file_path' => $result->filePath,
                'file_size' => $result->fileSize,
            ]);
            
        } catch (ReportNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
```

---

## Common Patterns

### Pattern 1: Dependency Injection

Always inject interfaces, never concrete classes:

```php
// ✅ CORRECT
public function __construct(
    private readonly ReportManager $reportManager
) {}

// ❌ WRONG - Concrete implementation
public function __construct(
    private readonly \App\Services\MyReportManager $manager
) {}
```

### Pattern 2: Multi-Tenancy

All repositories automatically scope by tenant. Ensure tenant context is set before any operation:

```php
// Tenant context is typically set by middleware
// The repository will filter by current tenant automatically

$report = $this->reportManager->findById($reportId);
// Only returns if report belongs to current tenant
```

### Pattern 3: Exception Handling

```php
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use Nexus\Reporting\Exceptions\ReportGenerationException;
use Nexus\Reporting\Exceptions\UnauthorizedReportException;

try {
    $result = $this->reportManager->generateReport($id);
} catch (ReportNotFoundException $e) {
    // Report definition not found
    return response()->json(['error' => 'Report not found'], 404);
} catch (UnauthorizedReportException $e) {
    // User lacks permission
    return response()->json(['error' => 'Unauthorized'], 403);
} catch (ReportGenerationException $e) {
    // Generation failed (query, export, or storage)
    Log::error('Report generation failed', [
        'report_id' => $id,
        'error' => $e->getMessage(),
    ]);
    return response()->json(['error' => 'Generation failed'], 500);
}
```

### Pattern 4: Scheduled Report Jobs

For scheduled reports, create a Laravel command or Symfony command that runs via cron:

```php
// Laravel
class ProcessScheduledReports extends Command
{
    protected $signature = 'reports:process-scheduled';
    
    public function handle(ReportJobHandler $handler): void
    {
        $handler->processScheduledJobs();
    }
}

// Add to Laravel scheduler (app/Console/Kernel.php)
$schedule->command('reports:process-scheduled')->everyMinute();
```

---

## Troubleshooting

### Issue: Interface not bound

**Error:**
```
Target interface [Nexus\Reporting\Contracts\ReportRepositoryInterface] is not instantiable.
```

**Solution:**
Ensure the interface is bound in your service provider:

**Laravel:**
```php
$this->app->singleton(
    ReportRepositoryInterface::class,
    EloquentReportRepository::class
);
```

**Symfony:**
```yaml
Nexus\Reporting\Contracts\ReportRepositoryInterface:
    class: App\Repository\ReportDefinitionRepository
```

---

### Issue: Tenant context missing

**Error:**
```
Call to a member function getCurrentTenantId() on null
```

**Solution:**
Ensure `Nexus\Tenant` package is installed and tenant middleware is active:

**Laravel:**
```php
// App\Http\Middleware\SetTenantContext
public function handle($request, Closure $next)
{
    $tenantId = $request->header('X-Tenant-ID');
    $this->tenantContext->setTenant($tenantId);
    return $next($request);
}
```

---

### Issue: Storage permission denied

**Error:**
```
Nexus\Reporting\Exceptions\ReportGenerationException: Storage failed - Permission denied
```

**Solution:**
1. Check file permissions on the storage directory
2. Verify the storage disk configuration
3. Ensure the web server user has write access

---

**Prepared By:** Nexus Architecture Team  
**Last Updated:** 2025-11-30

