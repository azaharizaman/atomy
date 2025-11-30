# Nexus\Export

**Framework-agnostic export/output rendering engine for converting structured data into downloadable formats.**

## Purpose

`Nexus\Export` is a stateless, atomic package that transforms domain data from any Nexus package into various output formats (PDF, Excel, CSV, JSON, etc.) through a standardized **ExportDefinition** intermediate representation. The package never knows the source domain (financial statements, payslips, purchase orders) or final usage—it simply converts structured data to the requested format.

This package handles the **"getting data out of the system"** use case, complementing the planned `Nexus\Import` package which will handle the reverse process.

## Key Design Principles

1. **Stateless & Atomic**: Zero state persistence, zero Laravel dependencies in core
2. **Schema-Validated**: All ExportDefinitions validated against versioned schema before processing
3. **Resilient**: Circuit breaker support for external formatters, rate limiting for webhooks
4. **Streaming-Capable**: Memory-efficient processing of large datasets (100K+ rows)
5. **Format-Agnostic**: Domain packages never reference output formats directly

## Architecture

```
Domain Package (Nexus\Accounting)
    ↓
ExportGeneratorInterface::toExportDefinition()
    ↓
ExportDefinition (validated, versioned JSON structure)
    ↓
ExportManager::export($definition, $format, $destination)
    ↓
ExportFormatterInterface (factory selects: PDF/Excel/CSV/etc.)
    ↓
ExportResult (file_path, success, metadata)
    ↓
ExportDestination (DOWNLOAD/EMAIL/STORAGE/WEBHOOK/PRINTER)
```

## Core Components

### Contracts (Public API)
- `ExportGeneratorInterface` - Converts domain data → ExportDefinition
- `ExportFormatterInterface` - Converts ExportDefinition → output format
- `TemplateEngineInterface` - Renders templates with variable substitution
- `ExportStorageInterface` - File persistence
- `ExportRepositoryInterface` - Export history tracking
- `DefinitionValidatorInterface` - Schema validation
- `FormatterCircuitBreakerInterface` - Failure handling for external formatters

### Value Objects
- `ExportDefinition` - Standardized data structure (metadata + structure + styling)
- `ExportMetadata` - Author, timestamp, schema version, watermark, security settings
- `ExportSection` - Hierarchical section with nesting (0-8 levels)
- `TableStructure` - Headers, rows, footers, column widths
- `ExportResult` - Execution result with file path, duration, success status

### Enums
- `ExportFormat` - PDF, EXCEL, CSV, JSON, XML, HTML, TXT, PRINTER
- `ExportDestination` - DOWNLOAD, EMAIL, STORAGE, PRINTER, WEBHOOK, DOCUMENT_LIBRARY

### Core Engine (Framework-Agnostic)
- `DefinitionValidator` - Validates ExportDefinition against schema v1.0
- `TemplateRenderer` - Variable substitution, conditionals, loops
- `DataTransformer` - Array transformations for different formats
- `CsvFormatter` - Streaming CSV generation using PHP generators
- `JsonFormatter` - UTF-8 JSON with pretty print
- `TxtFormatter` - ASCII tabular format
- `XmlFormatter` - Well-formed XML 1.0

## Installation

```bash
composer require nexus/export:"*@dev"
```

## Usage Examples

### 1. Generate Financial Statement PDF

```php
use Nexus\Accounting\Services\Export\FinancialStatementExportGenerator;
use Nexus\Export\Services\ExportManager;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportDestination;

// Domain package generates ExportDefinition
$generator = new FinancialStatementExportGenerator($statement);
$definition = $generator->toExportDefinition();

// Export to PDF for download
$result = $exportManager->export(
    $definition,
    ExportFormat::PDF,
    ExportDestination::DOWNLOAD
);

if ($result->isSuccessful()) {
    // Download file at $result->getFilePath()
}
```

### 2. Generate Payslip PDF with Email Delivery

```php
use Nexus\Payroll\Services\Export\PayslipExportGenerator;

$generator = new PayslipExportGenerator($payrollData);
$definition = $generator->toExportDefinition();

// Export with password protection
$definition->getMetadata()->setSecurity([
    'password' => 'employee-ic-number',
    'encryption' => 'AES-256'
]);

$result = $exportManager->export(
    $definition,
    ExportFormat::PDF,
    ExportDestination::EMAIL
);
```

### 3. Stream Large CSV Export

```php
use Nexus\Finance\Services\Export\GeneralLedgerExportGenerator;

$generator = new GeneralLedgerExportGenerator($ledgerData);
$definition = $generator->toExportDefinition();

// Stream for memory efficiency (100K+ rows)
$stream = $exportManager->stream(
    $definition,
    ExportFormat::CSV
);

foreach ($stream as $chunk) {
    echo $chunk; // Output directly to response
}
```

### 4. Export from Template

```php
// Use predefined template with runtime data
$result = $exportManager->exportFromTemplate(
    templateId: 'purchase-order-standard',
    data: [
        'vendor' => $vendor,
        'items' => $lineItems,
        'total' => $poTotal
    ],
    format: ExportFormat::PDF,
    destination: ExportDestination::STORAGE
);
```

## ExportDefinition Schema (v1.0)

```json
{
    "schema_version": "1.0",
    "metadata": {
        "title": "Balance Sheet",
        "author": "system",
        "generated_at": "2025-11-19T10:30:00Z",
        "watermark": "CONFIDENTIAL",
        "security": {
            "password": "secret123"
        }
    },
    "structure": [
        {
            "type": "section",
            "name": "Assets",
            "level": 0,
            "items": [
                {
                    "type": "line",
                    "label": "Cash and Cash Equivalents",
                    "value": "1000000.00",
                    "level": 1,
                    "styling": ["bold"]
                }
            ]
        },
        {
            "type": "table",
            "headers": ["Account", "Debit", "Credit", "Balance"],
            "rows": [
                ["1000", "500.00", "0.00", "500.00"],
                ["2000", "0.00", "300.00", "-300.00"]
            ],
            "footers": ["Total", "500.00", "300.00", "200.00"]
        }
    ]
}
```

## Integration Points

### With Nexus Packages
- **Nexus\Storage** - File persistence via `ExportStorageInterface`
- **Nexus\AuditLogger** - Log all export operations
- **Nexus\Tenant** - Multi-tenancy context
- **Nexus\Connector** - Rate limiting for webhooks, circuit breakers for external services
- **Nexus\Notifier** - Email delivery of exports
- **Nexus\Identity** - User context and RBAC

### With Domain Packages
- **Nexus\Accounting** - Financial statement exports
- **Nexus\Payroll** - Payslip PDF generation
- **Nexus\Receivable** - Invoice PDFs
- **Nexus\Payable** - Payment advice documents
- **Nexus\Procurement** - Purchase order printing
- **Nexus\Hrm** - Employee directory exports

## Performance Targets

- **PER-EXP-001**: Export 100K rows to CSV < 30s using streaming (< 50MB memory)
- **PER-EXP-002**: Financial statement PDF generation < 5s for 1K accounts
- **PER-EXP-003**: Definition validation < 100ms
- **PER-EXP-004**: Template rendering < 200ms for 1000 variables

## Security Features

- Password-protected PDFs for sensitive documents
- Watermarking support for draft/confidential documents
- Digital signatures for finalized statements
- Audit trail for all export operations
- Tenant isolation enforcement
- Rate limiting for webhook/email destinations

## Roadmap

### Phase 1 (MVP)
- [x] Core package structure
- [ ] ExportDefinition schema v1.0
- [ ] Native formatters (CSV, JSON, TXT, XML)
- [ ] Definition validator
- [ ] Template renderer
- [ ] ExportManager orchestration

### Phase 2 (Atomy Integration)
- [ ] PDF formatter (DomPDF)
- [ ] Excel formatter (Maatwebsite/Excel)
- [ ] HTML formatter (Blade templates)
- [ ] Database models & migrations
- [ ] Service provider bindings

### Phase 3 (Domain Integration)
- [ ] Financial statement generators
- [ ] Payslip generators
- [ ] Invoice generators
- [ ] Purchase order generators

### Phase 4 (Advanced Features)
- [ ] Printer destination support
- [ ] Webhook formatter with circuit breaker
- [ ] Schema versioning (v1.1+)
- [ ] Advanced template builder UI

## Documentation

### 📚 Complete Documentation

- **[Getting Started Guide](docs/getting-started.md)** - Quick start tutorial with examples
- **[API Reference](docs/api-reference.md)** - Complete API documentation for all interfaces, services, and value objects
- **[Integration Guide](docs/integration-guide.md)** - Laravel, Symfony, and custom PHP integration examples
- **[Basic Usage Examples](docs/examples/basic-usage.php)** - Simple export scenarios (CSV, JSON, XML, invoices, large datasets)
- **[Advanced Usage Examples](docs/examples/advanced-usage.php)** - Template rendering, custom formatters, nested sections, error handling

### 📋 Package Documentation

- **[REQUIREMENTS.md](REQUIREMENTS.md)** - Detailed package requirements (42 requirements)
- **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - Implementation tracking and metrics
- **[TEST_SUITE_SUMMARY.md](TEST_SUITE_SUMMARY.md)** - Test coverage plan (38 unit + 8 integration tests)
- **[VALUATION_MATRIX.md](VALUATION_MATRIX.md)** - Package valuation analysis ($121,600 estimated value)
- **[DOCUMENTATION_COMPLIANCE_SUMMARY.md](DOCUMENTATION_COMPLIANCE_SUMMARY.md)** - Documentation compliance report

### 🎯 Quick Links

- **Architecture:** See [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) for ExportDefinition intermediate representation pattern
- **Testing:** See [TEST_SUITE_SUMMARY.md](TEST_SUITE_SUMMARY.md) for planned test suite (46 tests)
- **Integration:** See [docs/integration-guide.md](docs/integration-guide.md) for framework integration examples
- **Examples:** See [docs/examples/](docs/examples/) for runnable code examples

---

## License

MIT License - See LICENSE file for details
