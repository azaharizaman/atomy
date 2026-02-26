# Getting Started with Nexus Reporting

## Prerequisites

- **PHP 8.3 or higher**
- **Composer**
- **Required Nexus Packages:**
  - `nexus/query-engine` - Query execution engine
  - `nexus/export` - File rendering (PDF, Excel, CSV, HTML, JSON)
  - `nexus/scheduler` - Scheduled report jobs
  - `nexus/notifier` - Multi-channel distribution
  - `nexus/storage` - File storage abstraction
  - `nexus/audit-logger` - Audit trail (optional)
  - `nexus/tenant` - Multi-tenant context

---

## Installation

```bash
composer require nexus/reporting:"*@dev"
```

This will install the Reporting package along with its dependencies.

---

## When to Use This Package

### ✅ Use Nexus Reporting for:
- **Scheduled business reports** - Daily, weekly, monthly, or custom cron schedules
- **Multi-format exports** - Same data rendered as PDF, Excel, CSV, JSON, or HTML
- **Automated distribution** - Email, Slack, webhooks, or in-app notifications
- **Report lifecycle management** - Active → Archived → Purged with configurable retention
- **Presentation layer orchestration** - Connecting Analytics queries to formatted output
- **Audit trail requirements** - Full lineage from query to generated report

### ❌ Do NOT use Nexus Reporting for:
- **Ad-hoc data queries** - Use `Nexus\QueryEngine` directly
- **File export only** - Use `Nexus\Export` directly
- **Real-time dashboards** - Use `Nexus\QueryEngine` with caching
- **One-off file downloads** - Use `Nexus\Export` with streaming

---

## Core Concepts

### 1. Report Definition
A **Report Definition** is the template for generating reports. It contains:
- Name and description
- Reference to an Analytics query
- Output format (PDF, Excel, CSV, JSON, HTML)
- Default parameters
- Optional custom template
- Schedule configuration
- Retention tier

### 2. Report Generation
**Report Generation** orchestrates:
1. Execute Analytics query to get data
2. Pass data to Export package for rendering
3. Store generated file via Storage package
4. Log generation to AuditLogger

### 3. Report Distribution
**Distribution** sends generated reports via Notifier:
- Email with attachment
- Slack message with file link
- Webhook with download URL
- In-app notification

### 4. Retention Lifecycle
Reports transition through **3 tiers**:
1. **ACTIVE** (90 days) - Hot storage, immediate access
2. **ARCHIVED** (~7 years) - Cold storage, slower access
3. **PURGED** - Permanently deleted

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                      ReportManager                               │
│  (Main Public API - Orchestrates all operations)                │
└─────────────────────┬───────────────────────────────────────────┘
                      │
    ┌─────────────────┼─────────────────┬─────────────────┐
    ▼                 ▼                 ▼                 ▼
┌─────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
│ Report  │   │  Report     │   │  Report     │   │  Report     │
│ Repo    │   │  Generator  │   │ Distributor │   │ Retention   │
└────┬────┘   └──────┬──────┘   └──────┬──────┘   └──────┬──────┘
     │               │                 │                  │
     │        ┌──────┴──────┐         │                  │
     │        ▼             ▼         ▼                  ▼
     │   ┌─────────┐  ┌─────────┐ ┌─────────┐      ┌─────────┐
     │   │Analytics│  │ Export  │ │Notifier │      │ Storage │
     │   └─────────┘  └─────────┘ └─────────┘      └─────────┘
     │
     ▼
┌─────────────────┐
│   Database      │
│ (Definitions)   │
└─────────────────┘
```

---

## Basic Configuration

### Step 1: Implement Repository Interface

The `ReportRepositoryInterface` handles persistence of report definitions and metadata for generated reports.

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use App\Models\ReportDefinition;

final readonly class EloquentReportRepository implements ReportRepositoryInterface
{
    public function findById(string $id): ReportDefinitionInterface
    {
        return ReportDefinition::findOrFail($id);
    }
    
    public function save(ReportDefinitionInterface $definition): void
    {
        $model = new ReportDefinition([
            'id' => $definition->getId(),
            'tenant_id' => $definition->getTenantId(),
            'name' => $definition->getName(),
            'query_id' => $definition->getQueryId(),
            'format' => $definition->getFormat()->value,
            'parameters' => $definition->getParameters(),
            'created_by' => $definition->getCreatedBy(),
        ]);
        $model->save();
    }
    
    public function update(ReportDefinitionInterface $definition): void
    {
        $model = ReportDefinition::findOrFail($definition->getId());
        $model->update([
            'name' => $definition->getName(),
            'parameters' => $definition->getParameters(),
        ]);
    }
    
    public function archive(string $id): void
    {
        $model = ReportDefinition::findOrFail($id);
        $model->update(['archived_at' => now()]);
    }
    
    public function findGeneratedReportById(string $id): array
    {
        $report = GeneratedReport::find($id);
        
        if (!$report) {
            throw ReportNotFoundException::forGeneratedReport($id);
        }
        
        return $report->toArray();
    }
}
```

### Step 2: Implement Report Generator

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Reporting\Contracts\ReportGeneratorInterface;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\ValueObjects\ReportResult;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\RetentionTier;
use Nexus\Reporting\Exceptions\ReportGenerationException;
use Nexus\QueryEngine\Contracts\AnalyticsManagerInterface;
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Storage\Contracts\StorageInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ReportGenerator implements ReportGeneratorInterface
{
    public function __construct(
        private AnalyticsManagerInterface $analytics,
        private ExportManagerInterface $export,
        private StorageInterface $storage,
    ) {}
    
    public function generate(
        ReportDefinitionInterface $definition,
        array $parameters = []
    ): ReportResult {
        $startTime = microtime(true);
        $reportId = (string) new Ulid();
        
        try {
            // 1. Execute Analytics query
            $mergedParams = array_merge($definition->getParameters(), $parameters);
            $queryResult = $this->analytics->executeQuery(
                $definition->getQueryId(),
                $mergedParams
            );
            
            // 2. Render via Export
            $content = $this->export->render(
                format: $definition->getFormat()->value,
                data: $queryResult->getData(),
                templateId: $definition->getTemplateId()
            );
            
            // 3. Store file
            $path = $this->buildFilePath($reportId, $definition->getFormat());
            $this->storage->put($path, $content);
            
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            
            return new ReportResult(
                reportId: $reportId,
                format: $definition->getFormat(),
                filePath: $path,
                fileSize: strlen($content),
                generatedAt: new \DateTimeImmutable(),
                durationMs: $durationMs,
                isSuccessful: true,
                error: null,
                retentionTier: RetentionTier::ACTIVE,
                queryResultId: $queryResult->getId()
            );
            
        } catch (\Throwable $e) {
            throw ReportGenerationException::queryExecutionFailed(
                $definition->getQueryId(),
                $e->getMessage()
            );
        }
    }
    
    public function generateFromQuery(
        string $queryId,
        ReportFormat $format,
        array $parameters = [],
        ?string $templateId = null
    ): ReportResult {
        // Ad-hoc generation without saved definition
        // ... implementation
    }
    
    public function previewReport(
        ReportDefinitionInterface $definition,
        int $rowLimit = 100
    ): string {
        // Generate with limited data
        // ... implementation
    }
    
    public function generateBatch(
        array $definitions,
        int $concurrencyLimit = 5
    ): array {
        $results = [];
        
        foreach ($definitions as $definition) {
            $results[$definition->getId()] = $this->generate($definition);
        }
        
        return $results;
    }
    
    private function buildFilePath(string $reportId, ReportFormat $format): string
    {
        $date = date('Y/m/d');
        return "reports/{$date}/{$reportId}.{$format->extension()}";
    }
}
```

### Step 3: Bind Interfaces in Service Provider

**Laravel:**

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
use App\Services\ReportGenerator;
use App\Services\ReportDistributor;
use App\Services\ReportRetentionManager;

class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository
        $this->app->singleton(
            ReportRepositoryInterface::class,
            EloquentReportRepository::class
        );
        
        // Generator
        $this->app->singleton(
            ReportGeneratorInterface::class,
            ReportGenerator::class
        );
        
        // Distributor
        $this->app->singleton(
            ReportDistributorInterface::class,
            ReportDistributor::class
        );
        
        // Retention
        $this->app->singleton(
            ReportRetentionInterface::class,
            ReportRetentionManager::class
        );
        
        // Main Manager
        $this->app->singleton(ReportManager::class);
    }
}
```

### Step 4: Use the ReportManager

```php
<?php

use Nexus\Reporting\Services\ReportManager;
use Nexus\Reporting\ValueObjects\ReportFormat;

class ReportController
{
    public function __construct(
        private readonly ReportManager $reportManager
    ) {}
    
    public function generate(string $reportId)
    {
        $result = $this->reportManager->generateReport($reportId);
        
        if ($result->isSuccessful) {
            return response()->download($result->filePath);
        }
        
        return response()->json(['error' => $result->error], 500);
    }
}
```

---

## Your First Report

### Create a Report Definition

```php
use Nexus\Reporting\ValueObjects\ReportFormat;

// Create a new report definition
$definition = $reportManager->createReport(
    name: 'Monthly Sales Summary',
    queryId: '01HABC123...',  // Your Analytics query ID
    format: ReportFormat::PDF,
    parameters: [
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
        'region' => 'APAC',
    ]
);

echo "Created report: {$definition->getId()}";
```

### Generate the Report

```php
$result = $reportManager->generateReport($definition->getId());

echo "Report generated: {$result->filePath}";
echo "File size: " . number_format($result->fileSize / 1024, 2) . " KB";
echo "Duration: {$result->durationMs}ms";
```

### Distribute to Recipients

```php
$distribution = $reportManager->distributeReport(
    reportId: $result->reportId,
    recipients: ['sales@company.com', 'manager@company.com'],
    channel: 'email'
);

echo "Sent to {$distribution->successCount} recipients";

if ($distribution->failureCount > 0) {
    echo "Failed: " . implode(', ', array_keys($distribution->errors));
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for complete interface documentation
- Check [Integration Guide](integration-guide.md) for Laravel and Symfony examples
- See [Examples](examples/) for more code samples
- Review [Retention Policies](#retention-lifecycle) for compliance requirements

---

## Troubleshooting

### Issue: "Report definition not found"

**Error:**
```
Nexus\Reporting\Exceptions\ReportNotFoundException: Report definition with ID 'xxx' not found
```

**Solution:**
Ensure the report definition exists and the tenant context matches. Check that your repository implementation correctly filters by tenant.

---

### Issue: "Cannot execute query"

**Error:**
```
Nexus\Reporting\Exceptions\UnauthorizedReportException: User lacks permission to execute query 'xxx'
```

**Solution:**
The ReportManager inherits permissions from the Analytics query. Ensure the user has access to the underlying Analytics query.

---

### Issue: "Export failed"

**Error:**
```
Nexus\Reporting\Exceptions\ReportGenerationException: Export rendering failed for format PDF
```

**Solution:**
1. Verify the Export package has the required renderer for the format
2. Check that all required template variables are provided
3. Ensure the data from Analytics is in the expected structure

---

**Prepared By:** Nexus Architecture Team  
**Last Updated:** 2025-11-30

