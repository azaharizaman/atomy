# Getting Started with Nexus Statutory

## Prerequisites

- PHP 8.3 or higher
- Composer
- Nexus\Finance package (for financial data extraction)
- Nexus\Period package (for period management)

## Installation

```bash
composer require nexus/statutory:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Generating statutory reports required by regulatory authorities
- ✅ Tax filings (P&L, Balance Sheet, Tax Forms)
- ✅ Payroll statutory calculations (EPF, SOCSO, PCB, etc.)
- ✅ Multi-format report generation (XBRL, PDF, CSV, JSON)
- ✅ Multi-country/multi-jurisdiction deployments
- ✅ Compliance with government filing requirements

Do NOT use this package for:
- ❌ Operational compliance (use Nexus\Compliance instead)
- ❌ Internal financial reporting (use Nexus\Finance/Accounting)
- ❌ Process enforcement (use Nexus\Compliance)
- ❌ Direct GL posting (use Nexus\Finance)

---

## Core Concepts

### Concept 1: Statutory vs Compliance

**Nexus\Statutory** (this package):
- **Purpose:** Generate reports for filing with authorities
- **Examples:** SSM financial statements, LHDN tax forms, EPF contribution reports
- **Focus:** "What must be filed"
- **Output:** XBRL files, PDF reports, CSV data

**Nexus\Compliance:**
- **Purpose:** Enforce operational processes
- **Examples:** ISO segregation of duties, SOX approval workflows
- **Focus:** "How the system must behave"
- **Output:** Process validation, configuration enforcement

**Key Distinction:** Compliance enforces process; Statutory generates reports.

---

### Concept 2: Adapter Pattern for Country-Specific Logic

The core Statutory package provides the **framework**; country-specific logic lives in **separate adapter packages**.

**Why Separate Adapters?**
1. **Licensing Flexibility:** Core is MIT; adapters can be commercial
2. **Tenant Isolation:** Each tenant binds to appropriate adapter based on country
3. **Extensibility:** New countries added without modifying core

**Example Adapter Packages:**
- `nexus/statutory-accounting-ssm` (Malaysian Company Act - Commercial)
- `nexus/statutory-payroll-mys` (EPF/SOCSO/PCB - Commercial)
- `nexus/statutory-accounting-mys-prop` (Proprietorship - Open-source)

**Default Adapters (Included in Core):**
- `DefaultAccountingAdapter`: Basic P&L and Balance Sheet (no taxonomy tags)
- `DefaultPayrollStatutoryAdapter`: Zero deductions (safe fallback)

---

### Concept 3: Report Metadata

Every statutory report has **metadata** that describes:
- **Schema Identifier:** Which regulatory schema (e.g., "SSM-FS-2023")
- **Schema Version:** Version of the schema (e.g., "v1.2.0")
- **Filing Frequency:** Monthly, Quarterly, Annual
- **Reporting Body:** Authority receiving the report (e.g., "SSM", "LHDN")
- **Recipient System URL:** Government portal URL
- **Output Format:** XBRL, PDF, CSV, JSON
- **Validation Rules:** Schema validation rules

**Purpose:** Metadata allows the system to:
- Pre-validate mappings before generating report
- Auto-detect missing GL-to-taxonomy mappings
- Display filing deadlines and requirements to users

---

### Concept 4: GL Account to Taxonomy Mapping

Statutory reports (especially XBRL) require mapping GL accounts to **taxonomy tags**.

**Example:**
```
GL Account 1000 (Cash) → Taxonomy Tag "Assets.CurrentAssets.CashAndCashEquivalents"
GL Account 2000 (AP)   → Taxonomy Tag "Liabilities.CurrentLiabilities.TradePayables"
```

**Where Mappings Are Stored:** Application layer (database table: `taxonomy_mappings`)

**Who Creates Mappings:**
- **User/Accountant:** Maps GL accounts to taxonomy tags in UI
- **System/Adapter:** Country-specific adapter declares valid taxonomy tags

**Validation:**
- Schema validator checks if all mandatory taxonomy tags have mappings
- Validator throws `ValidationException` if mappings are incomplete

---

### Concept 5: Multi-Format Output

The same financial data can be exported in multiple formats:

| Format | Use Case | Output |
|--------|----------|--------|
| **XBRL** | Government e-filing (SSM, LHDN) | XML with taxonomy tags |
| **PDF** | Human-readable statements | Formatted PDF report |
| **CSV** | Data import/export | Comma-separated values |
| **JSON** | API integration | JSON structure |

**Example:** Malaysian company generates financial statements:
- **XBRL** → Submit to SSM e-Filing portal
- **PDF** → Print for directors' review
- **CSV** → Export for auditor analysis

---

### Concept 6: Event-Driven Architecture

Statutory reports have a **lifecycle** with events:

```
Draft → Generated → Validated → Submitted → Accepted/Rejected
```

**Events:**
- `ReportGenerated`: Report file created
- `ReportValidated`: Schema validation passed
- `ReportSubmitted`: Submitted to authority
- `ReportAccepted`: Authority accepted submission
- `ReportRejected`: Authority rejected submission

**Integration:** Application layer can listen to events and:
- Send notifications to users
- Update audit logs
- Trigger approval workflows
- Store submission confirmations

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The core package defines interfaces; your application implements them.

**Required Implementations:**

1. **StatutoryReportRepositoryInterface** - Persist reports to database
2. **StatutoryReportInterface** - Entity representing a statutory report

**Example: Laravel Eloquent Implementation**

```php
<?php

namespace App\Repositories;

use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Contracts\StatutoryReportInterface;
use App\Models\StatutoryReport;

final readonly class EloquentStatutoryReportRepository implements StatutoryReportRepositoryInterface
{
    public function findById(string $id): ?StatutoryReportInterface
    {
        return StatutoryReport::find($id);
    }
    
    public function save(StatutoryReportInterface $report): void
    {
        $report->save();
    }
    
    public function findByTenant(string $tenantId, ?string $reportType = null): array
    {
        $query = StatutoryReport::where('tenant_id', $tenantId);
        
        if ($reportType) {
            $query->where('report_type', $reportType);
        }
        
        return $query->get()->all();
    }
}
```

---

### Step 2: Create Database Migrations (Application Layer)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Statutory reports table
        Schema::create('statutory_reports', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('report_type', 50); // 'financial_statement', 'payroll_deduction', etc.
            $table->date('start_date');
            $table->date('end_date');
            $table->string('format', 20); // 'xbrl', 'pdf', 'csv', 'json'
            $table->string('status', 20)->default('draft'); // 'draft', 'generated', 'submitted', 'accepted', 'rejected'
            $table->string('file_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'report_type']);
            $table->index(['tenant_id', 'status']);
        });
        
        // Taxonomy mappings table
        Schema::create('taxonomy_mappings', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('gl_account_id', 26);
            $table->string('taxonomy_tag', 255); // e.g., "Assets.CurrentAssets.Cash"
            $table->string('schema_identifier', 100); // e.g., "SSM-FS-2023"
            $table->string('schema_version', 20); // e.g., "v1.2.0"
            $table->timestamps();
            
            $table->unique(['tenant_id', 'gl_account_id', 'schema_identifier']);
        });
    }
};
```

---

### Step 3: Create Eloquent Models

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Statutory\Contracts\StatutoryReportInterface;

class StatutoryReport extends Model implements StatutoryReportInterface
{
    protected $fillable = [
        'id', 'tenant_id', 'report_type', 'start_date', 'end_date',
        'format', 'status', 'file_path', 'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    public function getReportType(): string
    {
        return $this->report_type;
    }
    
    public function getStartDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->start_date);
    }
    
    public function getEndDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->end_date);
    }
    
    public function getFormat(): string
    {
        return $this->format;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function getFilePath(): ?string
    {
        return $this->file_path;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }
}
```

---

### Step 4: Bind Interfaces in Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Statutory\Contracts\{
    StatutoryReportRepositoryInterface,
    TaxonomyReportGeneratorInterface,
    PayrollStatutoryInterface
};
use Nexus\Statutory\Adapters\{
    DefaultAccountingAdapter,
    DefaultPayrollStatutoryAdapter
};
use App\Repositories\EloquentStatutoryReportRepository;

class StatutoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            StatutoryReportRepositoryInterface::class,
            EloquentStatutoryReportRepository::class
        );
        
        // Bind accounting adapter (conditional based on tenant country)
        $this->app->singleton(
            TaxonomyReportGeneratorInterface::class,
            function () {
                $country = $this->getTenantCountry();
                
                return match ($country) {
                    'MY' => app(\Nexus\StatutoryAccountingSSM\SSMTaxonomyGenerator::class),
                    default => app(DefaultAccountingAdapter::class)
                };
            }
        );
        
        // Bind payroll adapter (conditional based on tenant country)
        $this->app->singleton(
            PayrollStatutoryInterface::class,
            function () {
                $country = $this->getTenantCountry();
                
                return match ($country) {
                    'MY' => app(\Nexus\StatutoryPayrollMYS\MalaysianPayrollAdapter::class),
                    default => app(DefaultPayrollStatutoryAdapter::class)
                };
            }
        );
    }
    
    private function getTenantCountry(): string
    {
        // Get country from tenant context
        return app(\Nexus\Tenant\Contracts\TenantContextInterface::class)
            ->getCurrentTenant()
            ->getCountry();
    }
}
```

---

### Step 5: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\StatutoryServiceProvider::class,
],
```

---

## Your First Integration

### Example: Generate Basic Financial Statement (No Taxonomy)

```php
<?php

use Nexus\Statutory\Contracts\StatutoryReportManagerInterface;
use Nexus\Statutory\ValueObjects\ReportFormat;

class FinancialStatementController
{
    public function __construct(
        private readonly StatutoryReportManagerInterface $statutoryManager
    ) {}
    
    public function generateStatement(Request $request)
    {
        // Generate P&L statement in JSON format
        $reportId = $this->statutoryManager->generateReport(
            tenantId: 'tenant-123',
            reportType: 'profit_loss',
            startDate: new \DateTimeImmutable('2025-01-01'),
            endDate: new \DateTimeImmutable('2025-12-31'),
            format: ReportFormat::JSON,
            options: ['include_details' => true]
        );
        
        // Retrieve generated report
        $report = $this->statutoryManager->getReport($reportId);
        
        // Get file path
        $filePath = $report->getFilePath();
        
        // Download report
        return response()->download($filePath);
    }
}
```

**Expected Output (JSON):**
```json
{
  "report_type": "profit_loss",
  "period": {
    "start_date": "2025-01-01",
    "end_date": "2025-12-31"
  },
  "revenue": 1000000.00,
  "expenses": 750000.00,
  "net_profit": 250000.00,
  "details": {
    "revenue_breakdown": [...],
    "expense_breakdown": [...]
  }
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Basic Examples](examples/basic-usage.php) for more code samples
- See [Advanced Examples](examples/advanced-usage.php) for country adapters and XBRL

---

## Troubleshooting

### Common Issues

**Issue 1: Interface not bound**

**Error:**
```
Target interface [Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface] is not instantiable.
```

**Cause:** Service provider not registered or interface not bound.

**Solution:**
1. Ensure `StatutoryServiceProvider` is registered in `config/app.php`
2. Verify interface bindings in service provider's `register()` method

---

**Issue 2: Default adapter used instead of country-specific adapter**

**Error:** Report generated but without country-specific taxonomy tags.

**Cause:** Conditional binding not working; default adapter always returned.

**Solution:**
1. Check `getTenantCountry()` method returns correct country code
2. Verify country-specific adapter package is installed (`nexus/statutory-accounting-ssm`)
3. Add debug logging to service provider binding:
```php
\Log::info('Country detected: ' . $country);
```

---

**Issue 3: Validation fails with "Missing mandatory tag"**

**Error:**
```
ValidationException: Missing mandatory tag: Assets.CurrentAssets.CashAndCashEquivalents
```

**Cause:** GL account not mapped to required taxonomy tag.

**Solution:**
1. Create taxonomy mapping in database:
```sql
INSERT INTO taxonomy_mappings (id, tenant_id, gl_account_id, taxonomy_tag, schema_identifier)
VALUES ('01HZQ...', 'tenant-123', '1000', 'Assets.CurrentAssets.CashAndCashEquivalents', 'SSM-FS-2023');
```
2. Or create mapping via UI (if available)

---

**Issue 4: Payroll deductions return zero**

**Behavior:** All deductions (EPF, SOCSO, PCB) return 0.00.

**Cause:** Using `DefaultPayrollStatutoryAdapter` (safe fallback).

**Solution:**
1. Install country-specific payroll package: `nexus/statutory-payroll-mys`
2. Bind `PayrollStatutoryInterface` to country-specific adapter in service provider
3. Verify tenant country code is correctly detected

---

## Performance Considerations

### Large Report Generation

For reports with 1000+ GL accounts:
- Use **batch processing** for data extraction
- Enable **caching** for taxonomy mappings
- Generate reports **asynchronously** via queue

**Example: Queue Job**
```php
dispatch(new GenerateStatutoryReportJob($tenantId, $reportType, $period));
```

### Multi-Tenant Optimization

- **Index:** Ensure `tenant_id` is indexed on all tables
- **Cache:** Cache taxonomy mappings per tenant (reduce DB queries)
- **Isolation:** Use tenant-scoped queries to prevent cross-tenant data leakage

---

**Ready to dive deeper?** Check out the [API Reference](api-reference.md) for complete interface documentation.
