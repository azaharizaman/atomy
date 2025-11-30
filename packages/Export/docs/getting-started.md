# Getting Started with Nexus Export

Welcome to **Nexus\Export**, a framework-agnostic export engine that converts structured domain data into various output formats (CSV, JSON, XML, HTML, TXT, PDF, Excel) via a standardized intermediate representation.

---

## Prerequisites

- **PHP:** 8.3 or higher
- **Composer:** Latest version
- **PSR-3 Logger:** Optional (for logging export events)

---

## Installation

### Step 1: Install via Composer

```bash
composer require nexus/export:"*@dev"
```

### Step 2: Verify Installation

Check that the package is installed:

```bash
composer show nexus/export
```

Expected output:
```
name     : nexus/export
descrip. : Framework-agnostic export engine with intermediate representation
versions : * dev-main
type     : library
license  : MIT License
```

---

## Core Concepts

### The Export Pipeline

Nexus\Export uses a **4-stage pipeline**:

```
Domain Data → ExportGeneratorInterface → ExportDefinition → ExportFormatterInterface → Output
```

**Stage 1: Domain Data**
- Your business entities (Invoice, Report, Inventory, etc.)

**Stage 2: ExportGeneratorInterface**
- Converts domain data into ExportDefinition (intermediate representation)
- Implemented by YOU in your domain package

**Stage 3: ExportDefinition**
- Universal schema-validated intermediate format
- Independent of domain and output format

**Stage 4: ExportFormatterInterface**
- Converts ExportDefinition into final output format (CSV, JSON, XML, etc.)
- Provided by Nexus\Export (or implemented by YOU for custom formats)

---

## The ExportDefinition Schema

**ExportDefinition** is the heart of Nexus\Export. It's a framework-agnostic, schema-validated data structure that represents ANY exportable data.

### Structure

```php
ExportDefinition {
    metadata: ExportMetadata,     // Title, author, timestamp, schema version
    structure: ExportSection[],   // Hierarchical sections (0-8 levels deep)
    formatHints: array           // Optional format-specific hints
}
```

### Example: Simple ExportDefinition

```php
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportMetadata;
use Nexus\Export\ValueObjects\ExportSection;

$definition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Monthly Sales Report',
        author: 'System',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection(
            title: 'Summary',
            content: [
                'total_sales' => 125000.00,
                'total_orders' => 450,
                'average_order_value' => 277.78
            ]
        )
    ]
);
```

---

## Your First Export

Let's export a simple invoice to CSV.

### Step 1: Create ExportDefinition

```php
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportMetadata;
use Nexus\Export\ValueObjects\ExportSection;
use Nexus\Export\ValueObjects\TableStructure;

// Create invoice data as ExportDefinition
$invoiceDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Customer Invoice INV-2024-001',
        author: 'System',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        // Header section
        new ExportSection(
            title: 'Invoice Header',
            content: [
                'invoice_number' => 'INV-2024-001',
                'invoice_date' => '2024-11-24',
                'customer_name' => 'Acme Corporation',
                'due_date' => '2024-12-24'
            ]
        ),
        
        // Line items table
        new ExportSection(
            title: 'Line Items',
            content: new TableStructure(
                headers: ['Item', 'Quantity', 'Unit Price', 'Total'],
                rows: [
                    ['Widget A', 10, 25.00, 250.00],
                    ['Widget B', 5, 50.00, 250.00],
                    ['Widget C', 2, 100.00, 200.00]
                ],
                footers: ['Total', '', '', 700.00]
            )
        )
    ]
);
```

### Step 2: Export to CSV

```php
use Nexus\Export\Services\ExportManager;
use Nexus\Export\Core\Formatters\CsvFormatter;
use Nexus\Export\Core\Engine\DefinitionValidator;
use Nexus\Export\ValueObjects\ExportFormat;
use Psr\Log\NullLogger;

// Create export manager
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::CSV => new CsvFormatter()
    ],
    templateEngine: null,  // Not needed for CSV
    logger: new NullLogger()
);

// Export to CSV
$result = $exportManager->export(
    definition: $invoiceDefinition,
    format: ExportFormat::CSV,
    destination: ExportDestination::DOWNLOAD
);

// Result contains file path, duration, success status
echo "Export successful: {$result->getFilePath()}\n";
echo "Generated in: {$result->getDuration()}ms\n";
```

### Step 3: View the Output

```csv
Invoice Header
invoice_number,invoice_date,customer_name,due_date
INV-2024-001,2024-11-24,Acme Corporation,2024-12-24

Line Items
Item,Quantity,Unit Price,Total
Widget A,10,25.00,250.00
Widget B,5,50.00,250.00
Widget C,2,100.00,200.00
Total,,,700.00
```

---

## Supported Formats

Nexus\Export supports **8 output formats**:

| Format | Enum | Native Formatter | Notes |
|--------|------|------------------|-------|
| **CSV** | `ExportFormat::CSV` | ✅ Yes (`CsvFormatter`) | Streaming support for large datasets |
| **JSON** | `ExportFormat::JSON` | ✅ Yes (`JsonFormatter`) | UTF-8, pretty print option |
| **XML** | `ExportFormat::XML` | ✅ Yes (`XmlFormatter`) | Well-formed XML 1.0 |
| **TXT** | `ExportFormat::TXT` | ✅ Yes (`TxtFormatter`) | ASCII tabular format |
| **HTML** | `ExportFormat::HTML` | ⏳ Planned | Requires template engine |
| **PDF** | `ExportFormat::PDF` | ⏳ Planned | Requires vendor library (app layer) |
| **Excel** | `ExportFormat::EXCEL` | ⏳ Planned | Requires PhpSpreadsheet (app layer) |
| **Printer** | `ExportFormat::PRINTER` | ⏳ Planned | Raw printer commands |

---

## Supported Destinations

Exports can be sent to **6 destinations**:

| Destination | Enum | Notes |
|-------------|------|-------|
| **Download** | `ExportDestination::DOWNLOAD` | Immediate file download (synchronous) |
| **Email** | `ExportDestination::EMAIL` | Send via Nexus\Notifier (requires app binding) |
| **Storage** | `ExportDestination::STORAGE` | Save to Nexus\Storage (S3, local, etc.) |
| **Printer** | `ExportDestination::PRINTER` | Send to network/local printer |
| **Webhook** | `ExportDestination::WEBHOOK` | HTTP POST to external endpoint |
| **Document Library** | `ExportDestination::DOCUMENT_LIBRARY` | Save to Nexus\Document |

---

## Framework Integration

### Laravel Integration

#### 1. Create Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Export\Services\ExportManager;
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Export\Core\Formatters\CsvFormatter;
use Nexus\Export\Core\Formatters\JsonFormatter;
use Nexus\Export\Core\Formatters\XmlFormatter;
use Nexus\Export\Core\Formatters\TxtFormatter;
use Nexus\Export\Core\Engine\TemplateRenderer;
use Nexus\Export\Core\Engine\DefinitionValidator;
use Nexus\Export\ValueObjects\ExportFormat;

class ExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExportManagerInterface::class, function ($app) {
            return new ExportManager(
                validator: new DefinitionValidator(),
                formatters: [
                    ExportFormat::CSV => new CsvFormatter(),
                    ExportFormat::JSON => new JsonFormatter(),
                    ExportFormat::XML => new XmlFormatter(),
                    ExportFormat::TXT => new TxtFormatter(),
                ],
                templateEngine: new TemplateRenderer(),
                logger: $app->make(\Psr\Log\LoggerInterface::class)
            );
        });
    }
}
```

#### 2. Register in `config/app.php`

```php
'providers' => [
    // ...
    App\Providers\ExportServiceProvider::class,
],
```

#### 3. Use in Controller

```php
namespace App\Http\Controllers;

use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportDestination;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ExportManagerInterface $exportManager
    ) {}
    
    public function exportInvoice(string $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        // Convert to ExportDefinition
        $definition = $invoice->toExportDefinition();
        
        // Export to CSV
        $result = $this->exportManager->export(
            definition: $definition,
            format: ExportFormat::CSV,
            destination: ExportDestination::DOWNLOAD
        );
        
        // Return download response
        return response()->download($result->getFilePath());
    }
}
```

---

## Common Patterns

### Pattern 1: Domain Entity Export

Implement `ExportGeneratorInterface` in your domain entity:

```php
namespace Nexus\Receivable\Models;

use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportMetadata;
use Nexus\Export\ValueObjects\ExportSection;
use Nexus\Export\ValueObjects\TableStructure;

class Invoice implements ExportGeneratorInterface
{
    public function toExportDefinition(): ExportDefinition
    {
        return new ExportDefinition(
            metadata: new ExportMetadata(
                title: "Invoice {$this->number}",
                author: $this->createdBy,
                generatedAt: new \DateTimeImmutable(),
                schemaVersion: '1.0'
            ),
            structure: [
                new ExportSection(
                    title: 'Header',
                    content: [
                        'number' => $this->number,
                        'date' => $this->date->format('Y-m-d'),
                        'customer' => $this->customer->name
                    ]
                ),
                new ExportSection(
                    title: 'Line Items',
                    content: new TableStructure(
                        headers: ['Description', 'Qty', 'Price', 'Total'],
                        rows: $this->lines->map(fn($line) => [
                            $line->description,
                            $line->quantity,
                            $line->unitPrice,
                            $line->total
                        ])->toArray(),
                        footers: ['Total', '', '', $this->total]
                    )
                )
            ]
        );
    }
}
```

### Pattern 2: Collection Export

Export a collection of entities:

```php
public function exportInvoiceList(array $invoiceIds): ExportDefinition
{
    $invoices = Invoice::whereIn('id', $invoiceIds)->get();
    
    return new ExportDefinition(
        metadata: new ExportMetadata(
            title: 'Invoice List',
            author: 'System',
            generatedAt: new \DateTimeImmutable(),
            schemaVersion: '1.0'
        ),
        structure: [
            new ExportSection(
                title: 'Invoices',
                content: new TableStructure(
                    headers: ['Number', 'Customer', 'Date', 'Amount', 'Status'],
                    rows: $invoices->map(fn($inv) => [
                        $inv->number,
                        $inv->customer->name,
                        $inv->date->format('Y-m-d'),
                        $inv->total,
                        $inv->status->value
                    ])->toArray()
                )
            )
        ]
    );
}
```

---

## Next Steps

Now that you understand the basics:

1. **Explore Advanced Features:** [API Reference](api-reference.md)
2. **See More Examples:** [Examples Directory](examples/)
3. **Framework Integration:** [Integration Guide](integration-guide.md)
4. **Custom Formatters:** Learn how to create custom formatters for your specific needs

---

## Troubleshooting

### Common Issues

**Issue:** `ValidationException: Section depth exceeds maximum of 8 levels`
- **Solution:** Flatten your section hierarchy. The maximum nesting depth is 8 levels (0-8).

**Issue:** `FormatterException: Column count mismatch in table`
- **Solution:** Ensure all rows in `TableStructure` have the same number of columns as headers.

**Issue:** `UnsupportedFormatException: No formatter registered for format`
- **Solution:** Register the formatter in `ExportManager` constructor.

**Issue:** Memory exhaustion when exporting large datasets
- **Solution:** Use CSV format with streaming support (automatically enabled for > 1000 rows).

---

## Support

- **Documentation:** [API Reference](api-reference.md)
- **Examples:** [examples/](examples/)
- **Issues:** GitHub Issues (monorepo)

---

**Last Updated:** 2025-11-24  
**Package Version:** 1.0.0  
**Maintained By:** Nexus Architecture Team
