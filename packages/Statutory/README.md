# Nexus\Statutory

Statutory reporting engine for generating tax and regulatory reports in country-specific formats (XBRL, JSON, XML, CSV).

## Overview

The Statutory package provides a framework-agnostic engine for generating statutory reports required by regulatory authorities. It supports multiple output formats and can be extended with country-specific adapters for compliance with local regulations.

## Features

- **Report Generation**: Generate statutory reports (P&L, Balance Sheet, Tax Forms, Payroll Reports)
- **Multiple Formats**: Support for JSON, XML, XBRL, CSV, PDF, Excel
- **Default Adapters**: Built-in default implementations for basic accounting and payroll
- **Country-Specific Adapters**: Extensible architecture for country-specific requirements
- **Metadata Management**: Comprehensive report metadata (schema, validation, filing frequency)
- **Validation Engine**: Schema validation before report submission
- **Framework-Agnostic**: Pure PHP with no Laravel dependencies

## Installation

```bash
composer require nexus/statutory
```

## Architecture

This package follows the Nexus architecture principles:

- **Framework-Agnostic**: No Laravel dependencies in core services
- **Contract-Driven**: All external dependencies defined via interfaces
- **Adapter Pattern**: Country-specific implementations via adapters
- **Default Implementations**: Safe defaults for basic functionality
- **Value Objects**: Immutable objects for domain concepts (FilingFrequency, ReportFormat)

### Package Structure

```
packages/Statutory/
├── composer.json
├── LICENSE
├── README.md
└── src/
    ├── Contracts/                          # Interfaces
    │   ├── PayrollStatutoryInterface.php
    │   ├── ReportMetadataInterface.php
    │   ├── StatutoryReportInterface.php
    │   ├── StatutoryReportRepositoryInterface.php
    │   └── TaxonomyReportGeneratorInterface.php
    ├── Adapters/                           # Default implementations
    │   ├── DefaultAccountingAdapter.php
    │   └── DefaultPayrollStatutoryAdapter.php
    ├── Services/                           # Business logic
    │   └── StatutoryReportManager.php
    ├── ValueObjects/                       # Immutable domain objects
    │   ├── FilingFrequency.php
    │   └── ReportFormat.php
    └── Exceptions/                         # Domain exceptions
        ├── CalculationException.php
        ├── DataExtractionException.php
        ├── InvalidDeductionTypeException.php
        ├── InvalidReportTypeException.php
        ├── ReportNotFoundException.php
        └── ValidationException.php
```

## Usage

### Generating Statutory Reports

```php
use Nexus\Statutory\Services\StatutoryReportManager;
use Nexus\Statutory\ValueObjects\ReportFormat;

// Generate a profit & loss report
$reportId = $reportManager->generateReport(
    tenantId: 'tenant-123',
    reportType: 'profit_loss',
    startDate: new DateTimeImmutable('2025-01-01'),
    endDate: new DateTimeImmutable('2025-12-31'),
    format: ReportFormat::JSON,
    options: ['include_details' => true]
);

// Get the generated report
$report = $reportManager->getReport($reportId);

// Get all reports for a tenant
$reports = $reportManager->getReports(
    tenantId: 'tenant-123',
    reportType: 'profit_loss',
    from: new DateTimeImmutable('2025-01-01'),
    to: new DateTimeImmutable('2025-12-31')
);
```

### Payroll Statutory Calculations

```php
use Nexus\Statutory\Adapters\DefaultPayrollStatutoryAdapter;

// Calculate statutory deductions (default: zero deductions)
$deductions = $payrollAdapter->calculateDeductions(
    tenantId: 'tenant-123',
    employeeId: 'emp-001',
    grossSalary: 5000.00,
    payDate: new DateTimeImmutable('2025-11-30'),
    employeeData: [
        'citizenship' => 'MYS',
        'tax_exemption' => false,
    ]
);
```

### Report Metadata

```php
use Nexus\Statutory\Adapters\DefaultAccountingAdapter;

$adapter = new DefaultAccountingAdapter($logger);
$metadata = $adapter->getReportMetadata('profit_loss');

echo $metadata->getReportName();           // "Profit & Loss Statement"
echo $metadata->getCountryCode();          // "DEFAULT"
echo $metadata->getFilingFrequency()->value; // "On-Demand"
echo $metadata->getSchemaVersion();        // "v1.0"

$formats = $metadata->getSupportedFormats();
foreach ($formats as $format) {
    echo $format->getMimeType();           // "application/json", "text/csv"
}
```

## Supported Report Formats

```php
use Nexus\Statutory\ValueObjects\ReportFormat;

ReportFormat::JSON;   // application/json (.json)
ReportFormat::XML;    // application/xml (.xml)
ReportFormat::XBRL;   // application/xbrl+xml (.xbrl)
ReportFormat::CSV;    // text/csv (.csv)
ReportFormat::PDF;    // application/pdf (.pdf)
ReportFormat::EXCEL;  // application/vnd...spreadsheetml.sheet (.xlsx)

// Check format capabilities
$format = ReportFormat::XBRL;
$format->isMachineReadable();         // true
$format->supportsDigitalSignature();  // true
```

## Filing Frequencies

```php
use Nexus\Statutory\ValueObjects\FilingFrequency;

FilingFrequency::MONTHLY;        // 12 filings per year
FilingFrequency::QUARTERLY;      // 4 filings per year
FilingFrequency::SEMI_ANNUALLY;  // 2 filings per year
FilingFrequency::ANNUALLY;       // 1 filing per year
FilingFrequency::BIENNIAL;       // Every 2 years
FilingFrequency::ON_DEMAND;      // No scheduled filing

// Get filing details
$frequency = FilingFrequency::QUARTERLY;
$frequency->getMonthInterval();   // 3
$frequency->getFilingsPerYear();  // 4
$frequency->isScheduled();        // true
```

## Default Adapters

The package includes two default adapters:

1. **DefaultAccountingAdapter**: Basic P&L, Balance Sheet, Trial Balance (JSON/CSV only)
2. **DefaultPayrollStatutoryAdapter**: Zero deductions (safe default for testing)

These adapters provide safe defaults when no country-specific adapter is configured.

## Creating Country-Specific Adapters

To create a country-specific adapter (e.g., Malaysia):

```php
namespace Nexus\Statutory\Adapters;

use Nexus\Statutory\Contracts\PayrollStatutoryInterface;

final class MalaysiaPayrollAdapter implements PayrollStatutoryInterface
{
    public function calculateDeductions(
        string $tenantId,
        string $employeeId,
        float $grossSalary,
        \DateTimeImmutable $payDate,
        array $employeeData = []
    ): array {
        return [
            'epf_employee' => $this->calculateEPF($grossSalary, 'employee'),
            'epf_employer' => $this->calculateEPF($grossSalary, 'employer'),
            'socso_employee' => $this->calculateSOCSO($grossSalary, 'employee'),
            'socso_employer' => $this->calculateSOCSO($grossSalary, 'employer'),
            'eis_employee' => $this->calculateEIS($grossSalary, 'employee'),
            'eis_employer' => $this->calculateEIS($grossSalary, 'employer'),
            'pcb' => $this->calculatePCB($grossSalary, $employeeData),
        ];
    }

    public function getCountryCode(): string
    {
        return 'MYS';
    }

    // ... implement other methods
}
```

## Integration with Applications

This package defines contracts that must be implemented by the consuming application:

1. **Repository Implementations**: Implement all repository interfaces with Eloquent models
2. **Entity Implementations**: Implement all entity interfaces
3. **Database Migrations**: Create required tables in application layer
4. **Service Provider Bindings**: Bind interfaces to implementations in IoC container
5. **Adapter Registration**: Register country-specific adapters based on feature flags

### Required Tables (Application Layer)

```sql
-- Statutory reports
statutory_reports (id, tenant_id, report_type, start_date, end_date, format, status, file_path, metadata, created_at, updated_at)

-- Report instances (for versioning)
statutory_report_instances (id, report_id, version, generated_at, generated_by, file_path, checksum)

-- Rate tables (for payroll calculations)
statutory_rate_tables (id, country_code, deduction_type, effective_from, effective_to, rate_config, created_at, updated_at)
```

## Dependencies

- **PHP**: ^8.3
- **nexus/finance**: *@dev (for financial data extraction)
- **nexus/period**: *@dev (for period management)
- **psr/log**: ^3.0 (for logging interface)

## Development

### Running Tests

```bash
composer test
```

### Code Style

This package follows PSR-12 coding standards.

---

## 📖 Documentation

### Quick Links
- 📘 [Getting Started Guide](docs/getting-started.md) - Setup, core concepts, and first integration
- 📚 [API Reference](docs/api-reference.md) - Complete interface and service documentation
- 🔧 [Integration Guide](docs/integration-guide.md) - Laravel and Symfony integration examples
- 💡 [Basic Examples](docs/examples/basic-usage.php) - Report generation basics
- 🚀 [Advanced Examples](docs/examples/advanced-usage.php) - Country adapters, XBRL, multi-format

### Package Documentation
- 📋 [Requirements](REQUIREMENTS.md) - Detailed requirements specifications (61 requirements)
- 📊 [Implementation Summary](IMPLEMENTATION_SUMMARY.md) - Development progress and metrics
- ✅ [Test Suite Summary](TEST_SUITE_SUMMARY.md) - Test coverage and strategy (55 tests planned)
- 💰 [Valuation Matrix](VALUATION_MATRIX.md) - Package valuation and ROI analysis ($95K)

### Additional Resources
- 🏗️ [Architecture Guidelines](../../ARCHITECTURE.md) - Nexus architecture principles
- 📖 [Package Reference](../../docs/NEXUS_PACKAGES_REFERENCE.md) - All Nexus packages overview
- 📑 [Compliance/Statutory Analysis](../../docs/COMPLIANCE_STATUTORY_READINESS_ANALYSIS.md) - Package separation rationale

## Integration with Other Packages

This package integrates with:
- **Nexus\Finance** - Financial data extraction (GL accounts, trial balance)
- **Nexus\Period** - Period validation and fiscal year management
- **Nexus\Tenant** - Multi-tenancy context
- **Nexus\Payroll** - Payroll statutory calculation delegation
- **Nexus\Accounting** - Financial statement generation
- **Nexus\Compliance** - Operational compliance (separate from statutory reporting)

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please follow the Nexus architecture principles:

1. Keep the package framework-agnostic
2. Define all dependencies via interfaces
3. Use immutable Value Objects for domain concepts
4. Place all business logic in services
5. No database access or migrations in this package
6. Country-specific logic belongs in separate adapter packages

## Support

For issues, questions, or contributions, please refer to the main Nexus monorepo documentation.
