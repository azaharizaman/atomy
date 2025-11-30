# Nexus\Export Quick Start Guide

## 5-Minute Integration Guide

### Step 1: Make Your Domain Entity Exportable

```php
namespace Nexus\Accounting\Services;

use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportMetadata;
use Nexus\Export\ValueObjects\ExportSection;
use Nexus\Export\ValueObjects\TableStructure;

class BalanceSheet implements ExportGeneratorInterface
{
    public function toExportDefinition(): ExportDefinition
    {
        return new ExportDefinition(
            metadata: new ExportMetadata(
                title: 'Balance Sheet',
                author: 'Accounting',
                generatedAt: new \DateTimeImmutable(),
                schemaVersion: '1.0',
                watermark: null,
                security: []
            ),
            structure: [
                new ExportSection(
                    name: 'Assets',
                    level: 0,
                    items: [
                        new TableStructure(
                            headers: ['Account', 'Amount'],
                            rows: [
                                ['Cash', 100000],
                                ['Inventory', 50000]
                            ],
                            footers: ['Total', 150000],
                            columnWidths: []
                        )
                    ],
                    styling: [],
                    metadata: []
                )
            ],
            formatHints: ['currency' => 'MYR']
        );
    }
}
```

### Step 2: Export from Controller

```php
use Nexus\Export\Services\ExportManager;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportDestination;

class ReportController extends Controller
{
    public function __construct(
        private readonly ExportManager $exportManager
    ) {}
    
    public function download(Request $request)
    {
        $balanceSheet = new BalanceSheet();
        
        $result = $this->exportManager->export(
            $balanceSheet->toExportDefinition(),
            ExportFormat::from($request->format), // 'pdf', 'excel', 'csv', etc.
            ExportDestination::DOWNLOAD
        );
        
        return response()->download($result->filePath);
    }
}
```

---

## Common Use Cases

### 1. Export to CSV (Streaming)
**Use when**: Exporting >10K rows

```php
return response()->streamDownload(function () use ($definition, $exportManager) {
    foreach ($exportManager->stream($definition, ExportFormat::CSV) as $chunk) {
        echo $chunk;
    }
}, 'export.csv');
```

### 2. Export to PDF
**Use when**: User wants formatted document

```php
$result = $exportManager->export(
    $definition,
    ExportFormat::PDF,
    ExportDestination::DOWNLOAD
);

return response()->download($result->filePath)
    ->deleteFileAfterSend();
```

### 3. Export to Email
**Use when**: Scheduled reports

```php
$result = $exportManager->export(
    $definition,
    ExportFormat::EXCEL,
    ExportDestination::EMAIL // Requires Atomy implementation
);
```

### 4. Export from Template
**Use when**: Pre-designed invoice/report templates

```php
$result = $exportManager->exportFromTemplate(
    templateId: 'invoice_template',
    context: [
        'invoiceNumber' => 'INV-001',
        'customer' => 'Acme Corp',
        'items' => [...]
    ],
    format: ExportFormat::PDF,
    destination: ExportDestination::DOWNLOAD
);
```

---

## ExportDefinition Cheat Sheet

### Basic Structure
```php
new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Report Title',              // REQUIRED
        author: 'Department Name',           // Optional
        generatedAt: new \DateTimeImmutable(), // REQUIRED
        schemaVersion: '1.0',                // REQUIRED
        watermark: 'CONFIDENTIAL',           // Optional
        security: ['classification' => 'internal'] // Optional
    ),
    structure: [...],                        // REQUIRED (ExportSection[])
    formatHints: ['currency' => 'MYR']       // Optional
)
```

### Section Types

#### 1. Text Section
```php
new ExportSection(
    name: 'Introduction',
    level: 0,
    items: [
        'This is a plain text paragraph.',
        'Another paragraph.'
    ],
    styling: [],
    metadata: []
)
```

#### 2. Table Section
```php
new ExportSection(
    name: 'Revenue Breakdown',
    level: 0,
    items: [
        new TableStructure(
            headers: ['Month', 'Revenue', 'Profit'],
            rows: [
                ['Jan', 100000, 20000],
                ['Feb', 120000, 25000]
            ],
            footers: ['Total', 220000, 45000],
            columnWidths: [20, 30, 30] // Optional
        )
    ],
    styling: [],
    metadata: []
)
```

#### 3. Nested Sections (Hierarchy)
```php
new ExportSection(
    name: 'Financial Statements',
    level: 0,
    items: [
        'Overview text',
        new ExportSection(
            name: 'Income Statement',
            level: 1, // Child level
            items: [...]
        ),
        new ExportSection(
            name: 'Balance Sheet',
            level: 1,
            items: [...]
        )
    ]
)
```

**Maximum nesting**: 8 levels (0-7)

#### 4. Key-Value Data
```php
new ExportSection(
    name: 'Metadata',
    level: 0,
    items: [
        ['Company' => 'Acme Corp', 'Prepared By' => 'Finance Dept']
    ]
)
```

---

## Available Formats

| Format | Use Case | Streaming | Template | Binary |
|--------|----------|-----------|----------|--------|
| **CSV** | Data export, Excel import | ✅ | ❌ | ❌ |
| **JSON** | API, data interchange | ⚠️ Chunked | ❌ | ❌ |
| **XML** | XBRL, statutory reports | ❌ | ❌ | ❌ |
| **HTML** | Email, web preview | ❌ | ✅ | ❌ |
| **TXT** | ASCII reports, logs | ✅ | ❌ | ❌ |
| **PDF** | Formatted documents | ❌ | ✅ | ✅ |
| **EXCEL** | Spreadsheets | ❌ | ❌ | ✅ |
| **PRINTER** | Direct printing (Phase 2) | ❌ | ✅ | ✅ |

---

## Available Destinations

| Destination | Use Case | Rate Limited | Requires Auth |
|-------------|----------|--------------|---------------|
| **DOWNLOAD** | User-initiated export | ❌ | ✅ (Session) |
| **EMAIL** | Scheduled reports | ✅ (60/min) | ✅ (SMTP) |
| **STORAGE** | Archival, compliance | ❌ | ✅ (Storage) |
| **WEBHOOK** | Integration, automation | ✅ (60/min) | ✅ (API key) |
| **PRINTER** | Direct printing (Phase 2) | ❌ | ✅ (Device) |
| **DOCUMENT_LIBRARY** | Future (requires Nexus\Document) | ❌ | ✅ |

---

## Validation Rules

### ExportDefinition Schema v1.0

✅ **Valid**:
- Title: Non-empty string
- GeneratedAt: Valid DateTimeImmutable
- SchemaVersion: '1.0'
- Section nesting: 0-8 levels
- Table columns: Consistent across headers/rows/footers

❌ **Invalid**:
- Missing title
- Section level mismatch (declared level ≠ actual depth)
- Nesting depth >8
- Table rows with different column counts
- Unsupported schema version

### Manual Validation
```php
use Nexus\Export\Core\Engine\DefinitionValidator;

$validator = new DefinitionValidator();

// Get errors as array
$errors = $validator->validate($definition);
if (!empty($errors)) {
    // Handle errors: ['metadata.title' => ['Title is required']]
}

// Or throw exception
try {
    $validator->validateOrFail($definition);
} catch (\Nexus\Export\Exceptions\InvalidDefinitionException $e) {
    // Handle validation failure
}
```

---

## Template Syntax (Mustache-like)

### Variables
```
{{ variable }}
{{ user.name }}
{{ items.0.price }}
```

### Conditionals
```
{{#if isPaid}}
Status: PAID
{{/if}}
```

### Loops
```
{{#each items}}
- {{ name }}: {{ price }}
{{/each}}
```

### Example Template
```
Invoice #{{ invoiceNumber }}
Date: {{ date }}

Bill To:
{{ customer.name }}
{{ customer.address }}

{{#if customer.taxId}}
Tax ID: {{ customer.taxId }}
{{/if}}

Items:
{{#each items}}
- {{ description }}: {{ quantity }} x {{ unitPrice }} = {{ total }}
{{/each}}

{{#if discount}}
Discount: -{{ discount }}
{{/if}}

Total: {{ grandTotal }}
```

---

## Error Handling

### Exception Hierarchy
```
\Nexus\Export\Exceptions\ExportException
├── InvalidDefinitionException      // Schema validation failed
├── FormatterException              // Formatter processing error
├── TemplateException               // Template rendering error
│   └── TemplateNotFoundException   // Template ID not found
├── UnsupportedFormatException      // Format not registered
└── UnsupportedDestinationException // Destination not implemented
```

### Best Practices
```php
use Nexus\Export\Exceptions\InvalidDefinitionException;
use Nexus\Export\Exceptions\UnsupportedFormatException;
use Nexus\Export\Exceptions\ExportException;

try {
    $result = $exportManager->export($definition, $format, $destination);
    
    if (!$result->success) {
        Log::error('Export failed', ['error' => $result->error]);
        return response()->json(['error' => $result->error], 500);
    }
    
    return response()->download($result->filePath);
    
} catch (InvalidDefinitionException $e) {
    // Handle schema validation errors
    return response()->json(['errors' => $e->getMessage()], 422);
    
} catch (UnsupportedFormatException $e) {
    // Handle missing formatter
    return response()->json(['error' => 'Format not supported'], 400);
    
} catch (ExportException $e) {
    // Handle generic export errors
    Log::error('Export system error', ['exception' => $e]);
    return response()->json(['error' => 'Export failed'], 500);
}
```

---

## Performance Tips

### 1. Use Streaming for Large Datasets
**Threshold**: >10K rows

```php
// ❌ BAD: Loads entire dataset into memory
$result = $exportManager->export($definition, ExportFormat::CSV, ExportDestination::DOWNLOAD);

// ✅ GOOD: Streams rows one at a time
return response()->streamDownload(function () use ($definition, $exportManager) {
    foreach ($exportManager->stream($definition, ExportFormat::CSV) as $chunk) {
        echo $chunk;
    }
}, 'export.csv');
```

### 2. Use Generators for Row Generation
```php
// ❌ BAD: Loads all rows into array
$rows = $this->repository->getAllItems()->toArray();

// ✅ GOOD: Yields rows one at a time
function generateRows(): \Generator {
    foreach ($this->repository->cursor() as $item) {
        yield [$item->sku, $item->name, $item->quantity];
    }
}

$rows = iterator_to_array($this->generateRows());
```

### 3. Choose the Right Format
- **CSV**: Fastest, smallest file size, best for data export
- **JSON**: Structured, good for APIs
- **PDF**: Slowest, largest file size, best for presentation
- **Excel**: Moderate speed, structured, best for analysis

---

## Testing Your Exports

### Unit Test: ExportGenerator
```php
use Tests\TestCase;
use Nexus\Accounting\Services\BalanceSheet;

class BalanceSheetTest extends TestCase
{
    public function test_generates_valid_export_definition()
    {
        $balanceSheet = new BalanceSheet();
        $definition = $balanceSheet->toExportDefinition();
        
        $this->assertInstanceOf(ExportDefinition::class, $definition);
        $this->assertEquals('Balance Sheet', $definition->metadata->title);
        $this->assertEquals('1.0', $definition->metadata->schemaVersion);
        $this->assertNotEmpty($definition->structure);
    }
}
```

### Integration Test: Full Export Pipeline
```php
use Nexus\Export\Services\ExportManager;
use Nexus\Export\ValueObjects\ExportFormat;

class ExportIntegrationTest extends TestCase
{
    public function test_exports_balance_sheet_to_csv()
    {
        $manager = app(ExportManager::class);
        $balanceSheet = new BalanceSheet();
        
        $result = $manager->export(
            $balanceSheet->toExportDefinition(),
            ExportFormat::CSV,
            ExportDestination::DOWNLOAD
        );
        
        $this->assertTrue($result->success);
        $this->assertFileExists($result->filePath);
        
        $content = file_get_contents($result->filePath);
        $this->assertStringContainsString('Balance Sheet', $content);
        $this->assertStringContainsString('Assets', $content);
    }
}
```

---

## Troubleshooting

### "Package nexus/export not found"
**Solution**: Add repository to `composer.json`:
```json
"repositories": [
    {
        "type": "path",
        "url": "../../packages/Export"
    }
]
```

### "Class ExportManager not found"
**Solution**: Register service provider in `config/app.php`:
```php
'providers' => [
    // ...
    Nexus\Export\ExportServiceProvider::class,
]
```

### "Formatter not found for format: pdf"
**Solution**: Register PDF formatter in `AppServiceProvider`:
```php
$this->app->singleton(ExportManager::class, function ($app) {
    return new ExportManager(
        formatters: [
            'pdf' => new \App\Services\Export\PdfFormatter()
        ],
        // ...
    );
});
```

### "Section nesting depth exceeds maximum"
**Solution**: Limit nesting to 8 levels (0-7):
```php
// ❌ BAD: Level 9 (too deep)
new ExportSection(name: 'Root', level: 0, items: [
    new ExportSection(name: 'L1', level: 1, items: [
        new ExportSection(name: 'L2', level: 2, items: [
            // ... 7 more levels
        ])
    ])
])

// ✅ GOOD: Flatten hierarchy
new ExportSection(name: 'Root', level: 0, items: [
    new ExportSection(name: 'Category A', level: 1, items: [...]),
    new ExportSection(name: 'Category B', level: 1, items: [...])
])
```

---

## Next Steps

1. **Implement PDF/Excel formatters** in Atomy (`app/Services/Export/`)
2. **Register formatters** in `AppServiceProvider`
3. **Create export endpoints** in controllers
4. **Add audit logging** for compliance
5. **Implement email/webhook delivery** for destinations
6. **Add rate limiting** for scheduled exports

---

## Related Packages

- **Nexus\Storage**: File storage for STORAGE destination
- **Nexus\Notifier**: Email delivery for EMAIL destination
- **Nexus\Connector**: Webhook delivery, circuit breaker, rate limiting
- **Nexus\AuditLogger**: Audit trail for export operations
- **Nexus\Tenant**: Multi-tenancy context for exports
- **Nexus\Document**: Future integration for persistent exports

---

## Resources

- Full Implementation Guide: `/docs/EXPORT_IMPLEMENTATION_SUMMARY.md`
- Package README: `/packages/Export/README.md`
- Architecture Guidelines: `/ARCHITECTURE.md`
