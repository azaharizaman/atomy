# API Reference: Export

Complete API documentation for all public interfaces, services, value objects, and enums in Nexus\Export.

---

## Table of Contents

1. [Interfaces](#interfaces)
   - [ExportGeneratorInterface](#exportgeneratorinterface)
   - [ExportFormatterInterface](#exportformatterinterface)
   - [TemplateEngineInterface](#templateengineinterface)
   - [DefinitionValidatorInterface](#definitionvalidatorinterface)
2. [Services](#services)
   - [ExportManager](#exportmanager)
3. [Value Objects](#value-objects)
   - [ExportDefinition](#exportdefinition)
   - [ExportMetadata](#exportmetadata)
   - [ExportSection](#exportsection)
   - [TableStructure](#tablestructure)
   - [ExportResult](#exportresult)
4. [Enums](#enums)
   - [ExportFormat](#exportformat)
   - [ExportDestination](#exportdestination)
5. [Exceptions](#exceptions)

---

## Interfaces

### ExportGeneratorInterface

**Purpose:** Contract for domain packages to convert their data structures into ExportDefinition.

**Namespace:** `Nexus\Export\Contracts`

**Implementations:** Domain packages (Receivable, Payable, Finance, etc.)

```php
interface ExportGeneratorInterface
{
    /**
     * Convert domain data to standardized export definition
     * 
     * @return ExportDefinition Validated export definition
     * @throws InvalidDefinitionException
     */
    public function toExportDefinition(): ExportDefinition;

    /**
     * Get supported schema versions
     * 
     * @return string Semantic version range (e.g., '1.0-1.2')
     */
    public function supportsSchemaVersion(): string;
}
```

**Example Implementation:**

```php
namespace Nexus\Receivable\Models;

use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\Export\ValueObjects\ExportDefinition;

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
            structure: $this->buildStructure()
        );
    }
    
    public function supportsSchemaVersion(): string
    {
        return '1.0';
    }
}
```

---

### ExportFormatterInterface

**Purpose:** Contract for converting ExportDefinition into specific output formats.

**Namespace:** `Nexus\Export\Contracts`

**Implementations:** CsvFormatter, JsonFormatter, XmlFormatter, TxtFormatter (in package), PdfFormatter, ExcelFormatter (in app layer)

```php
interface ExportFormatterInterface
{
    /**
     * Convert export definition to formatted output
     * 
     * @param ExportDefinition $definition Validated export definition
     * @return string Binary content or file path depending on format
     * @throws FormatterException
     */
    public function format(ExportDefinition $definition): string;

    /**
     * Get supported export format
     * 
     * @return ExportFormat
     */
    public function getFormat(): ExportFormat;

    /**
     * Check if formatter supports streaming output
     * 
     * @return bool
     */
    public function supportsStreaming(): bool;

    /**
     * Check if formatter requires external service
     * 
     * @return bool
     */
    public function requiresExternalService(): bool;

    /**
     * Get required schema version
     * 
     * @return string Semantic version (e.g., '1.0')
     */
    public function getRequiredSchemaVersion(): string;
}
```

**Example Implementation:**

```php
namespace Nexus\Export\Core\Formatters;

use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportFormat;

final readonly class CsvFormatter implements ExportFormatterInterface
{
    public function format(ExportDefinition $definition): string
    {
        // Generate CSV content
        $csv = fopen('php://temp', 'r+');
        
        foreach ($definition->getStructure() as $section) {
            $this->formatSection($csv, $section);
        }
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        return $content;
    }
    
    public function getFormat(): ExportFormat
    {
        return ExportFormat::CSV;
    }
    
    public function supportsStreaming(): bool
    {
        return true;
    }
    
    public function requiresExternalService(): bool
    {
        return false;
    }
    
    public function getRequiredSchemaVersion(): string
    {
        return '1.0';
    }
}
```

---

### TemplateEngineInterface

**Purpose:** Contract for rendering templates with variable substitution, conditionals, and loops.

**Namespace:** `Nexus\Export\Contracts`

**Implementation:** TemplateRenderer (in package)

```php
interface TemplateEngineInterface
{
    /**
     * Render template with variable substitution
     * 
     * @param string $template Template string with {{variables}}
     * @param array $data Associative array of template data
     * @return string Rendered template
     * @throws TemplateException
     */
    public function render(string $template, array $data): string;

    /**
     * Check if template is valid
     * 
     * @param string $template Template string
     * @return bool
     */
    public function validate(string $template): bool;

    /**
     * Get supported template syntax version
     * 
     * @return string Version (e.g., '1.0')
     */
    public function getSyntaxVersion(): string;
}
```

**Supported Template Syntax:**

- **Variables:** `{{variable}}`, `{{nested.property}}`
- **Conditionals:** `@if(condition)...@else...@endif`
- **Loops:** `@foreach(items as item)...@endforeach`
- **Filters:** `{{date|date:Y-m-d}}`, `{{amount|number:2}}`, `{{name|upper}}`

**Example:**

```php
$template = <<<'TEMPLATE'
Invoice: {{metadata.title}}
Generated: {{metadata.generatedAt|date:Y-m-d}}

@if(customer.vip)
VIP Customer: {{customer.name}}
@else
Customer: {{customer.name}}
@endif

Line Items:
@foreach(lineItems as item)
- {{item.description}}: {{item.total|number:2}}
@endforeach
TEMPLATE;

$rendered = $templateEngine->render($template, [
    'metadata' => [
        'title' => 'INV-2024-001',
        'generatedAt' => new \DateTimeImmutable('2024-11-24')
    ],
    'customer' => ['name' => 'Acme Corp', 'vip' => true],
    'lineItems' => [
        ['description' => 'Widget A', 'total' => 250.00],
        ['description' => 'Widget B', 'total' => 500.00]
    ]
]);
```

---

### DefinitionValidatorInterface

**Purpose:** Contract for validating ExportDefinitions against schema rules.

**Namespace:** `Nexus\Export\Contracts`

**Implementation:** DefinitionValidator (in package)

```php
interface DefinitionValidatorInterface
{
    /**
     * Validate export definition against schema
     * 
     * @param ExportDefinition $definition Definition to validate
     * @return bool True if valid
     * @throws ValidationException If validation fails
     */
    public function validate(ExportDefinition $definition): bool;

    /**
     * Get validation errors
     * 
     * @return array<string> Array of error messages
     */
    public function getErrors(): array;

    /**
     * Get supported schema version
     * 
     * @return string Version (e.g., '1.0')
     */
    public function getSchemaVersion(): string;
}
```

**Validation Rules:**

- ✅ Metadata must include title, author, generatedAt, schemaVersion
- ✅ Schema version must be supported (currently '1.0')
- ✅ Section hierarchy must not exceed 8 levels (0-8)
- ✅ Table headers and rows must have consistent column counts
- ✅ Table footers (if present) must match header column count
- ✅ All required fields must be present

---

## Services

### ExportManager

**Purpose:** Main orchestration service for export pipeline.

**Namespace:** `Nexus\Export\Services`

**Constructor:**

```php
final readonly class ExportManager
{
    public function __construct(
        private DefinitionValidatorInterface $validator,
        private array $formatters,  // Array of ExportFormatterInterface indexed by ExportFormat
        private ?TemplateEngineInterface $templateEngine = null,
        private ?\Psr\Log\LoggerInterface $logger = null
    ) {}
}
```

**Methods:**

#### `export()`

Export ExportDefinition to specified format and destination.

```php
public function export(
    ExportDefinition $definition,
    ExportFormat $format,
    ExportDestination $destination,
    array $options = []
): ExportResult
```

**Parameters:**
- `$definition`: Validated ExportDefinition
- `$format`: Target format (ExportFormat enum)
- `$destination`: Where to send output (ExportDestination enum)
- `$options`: Optional configuration (delimiter, encoding, etc.)

**Returns:** `ExportResult` with file path, duration, success status

**Throws:**
- `ValidationException`: Invalid ExportDefinition
- `UnsupportedFormatException`: No formatter registered
- `FormatterException`: Formatter error

**Example:**

```php
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::CSV => new CsvFormatter(),
        ExportFormat::JSON => new JsonFormatter(),
    ],
    templateEngine: new TemplateRenderer(),
    logger: $logger
);

$result = $exportManager->export(
    definition: $invoiceDefinition,
    format: ExportFormat::CSV,
    destination: ExportDestination::DOWNLOAD,
    options: ['delimiter' => ';', 'encoding' => 'UTF-8']
);

echo "Export completed in {$result->getDuration()}ms\n";
echo "File: {$result->getFilePath()}\n";
```

---

## Value Objects

### ExportDefinition

**Purpose:** Universal intermediate representation for exportable data.

**Namespace:** `Nexus\Export\ValueObjects`

**Constructor:**

```php
final readonly class ExportDefinition
{
    public function __construct(
        public ExportMetadata $metadata,
        public array $structure,  // Array of ExportSection
        public array $formatHints = []
    ) {}
}
```

**Methods:**

```php
// Serialize to JSON
public function toJson(): string

// Deserialize from JSON
public static function fromJson(string $json): self

// Get metadata
public function getMetadata(): ExportMetadata

// Get structure
public function getStructure(): array

// Get format hints
public function getFormatHints(): array
```

**Example:**

```php
$definition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Monthly Report',
        author: 'System',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection('Summary', ['total' => 1000]),
        new ExportSection('Details', new TableStructure(...))
    ],
    formatHints: [
        'pdf' => ['orientation' => 'landscape'],
        'excel' => ['sheetName' => 'Report']
    ]
);

// Serialize
$json = $definition->toJson();

// Deserialize
$restored = ExportDefinition::fromJson($json);
```

---

### ExportMetadata

**Purpose:** Metadata for export (title, author, timestamp, schema version).

**Namespace:** `Nexus\Export\ValueObjects`

**Constructor:**

```php
final readonly class ExportMetadata
{
    public function __construct(
        public string $title,
        public string $author,
        public \DateTimeImmutable $generatedAt,
        public string $schemaVersion,
        public ?string $description = null,
        public ?string $watermark = null,
        public ?array $security = null,
        public ?array $custom = null
    ) {}
}
```

**Example:**

```php
$metadata = new ExportMetadata(
    title: 'Financial Statement Q4 2024',
    author: 'CFO',
    generatedAt: new \DateTimeImmutable(),
    schemaVersion: '1.0',
    description: 'Quarterly financial statement',
    watermark: 'CONFIDENTIAL',
    security: [
        'classification' => 'restricted',
        'retention_days' => 2555  // 7 years
    ],
    custom: ['department' => 'Finance']
);
```

---

### ExportSection

**Purpose:** Hierarchical section in export structure (supports 0-8 nesting levels).

**Namespace:** `Nexus\Export\ValueObjects`

**Constructor:**

```php
final readonly class ExportSection
{
    public function __construct(
        public string $title,
        public mixed $content,  // Array, TableStructure, or scalar
        public int $level = 0,
        public array $children = []
    ) {}
}
```

**Methods:**

```php
// Get section title
public function getTitle(): string

// Get section content
public function getContent(): mixed

// Get nesting level (0-8)
public function getLevel(): int

// Get child sections
public function getChildren(): array

// Calculate depth recursively
public function getDepth(): int
```

**Example:**

```php
$section = new ExportSection(
    title: 'Financial Summary',
    content: [
        'revenue' => 500000,
        'expenses' => 300000,
        'profit' => 200000
    ],
    level: 0,
    children: [
        new ExportSection(
            title: 'Revenue Breakdown',
            content: new TableStructure(...),
            level: 1
        )
    ]
);
```

---

### TableStructure

**Purpose:** Tabular data with headers, rows, footers, and column configuration.

**Namespace:** `Nexus\Export\ValueObjects`

**Constructor:**

```php
final readonly class TableStructure
{
    public function __construct(
        public array $headers,
        public array $rows,
        public ?array $footers = null,
        public ?array $columnWidths = null,
        public ?array $columnAlignments = null
    ) {}
}
```

**Validation Rules:**
- All rows must have same column count as headers
- Footers (if present) must have same column count as headers
- Column widths (if present) must match header count

**Example:**

```php
$table = new TableStructure(
    headers: ['Product', 'Quantity', 'Unit Price', 'Total'],
    rows: [
        ['Widget A', 10, 25.00, 250.00],
        ['Widget B', 5, 50.00, 250.00],
        ['Widget C', 2, 100.00, 200.00]
    ],
    footers: ['Total', '', '', 700.00],
    columnWidths: [40, 10, 15, 15],
    columnAlignments: ['left', 'right', 'right', 'right']
);
```

---

### ExportResult

**Purpose:** Result of export operation with metadata.

**Namespace:** `Nexus\Export\ValueObjects`

**Constructor:**

```php
final readonly class ExportResult
{
    public function __construct(
        public bool $success,
        public string $filePath,
        public int $duration,  // Milliseconds
        public ?string $errorMessage = null
    ) {}
}
```

**Methods:**

```php
// Check if export succeeded
public function isSuccess(): bool

// Get output file path
public function getFilePath(): string

// Get execution duration (ms)
public function getDuration(): int

// Get error message (if failed)
public function getErrorMessage(): ?string
```

**Example:**

```php
$result = new ExportResult(
    success: true,
    filePath: '/tmp/export_invoice_INV-2024-001.csv',
    duration: 125  // 125ms
);

if ($result->isSuccess()) {
    echo "Export completed in {$result->getDuration()}ms\n";
    echo "File saved to: {$result->getFilePath()}\n";
} else {
    echo "Export failed: {$result->getErrorMessage()}\n";
}
```

---

## Enums

### ExportFormat

**Purpose:** Supported export formats (8 total).

**Namespace:** `Nexus\Export\ValueObjects`

**Cases:**

```php
enum ExportFormat: string
{
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case JSON = 'json';
    case XML = 'xml';
    case HTML = 'html';
    case TXT = 'txt';
    case PRINTER = 'printer';
}
```

**Methods:**

```php
// Get MIME type for format
public function getMimeType(): string

// Get file extension
public function getFileExtension(): string

// Check if binary format
public function isBinary(): bool

// Check if supports streaming
public function supportsStreaming(): bool

// Check if requires template
public function requiresTemplate(): bool
```

**Example:**

```php
$format = ExportFormat::CSV;

echo $format->getMimeType();         // "text/csv"
echo $format->getFileExtension();    // ".csv"
echo $format->isBinary() ? 'Yes' : 'No';           // "No"
echo $format->supportsStreaming() ? 'Yes' : 'No';  // "Yes"
echo $format->requiresTemplate() ? 'Yes' : 'No';   // "No"
```

---

### ExportDestination

**Purpose:** Supported export destinations (6 total).

**Namespace:** `Nexus\Export\ValueObjects`

**Cases:**

```php
enum ExportDestination: string
{
    case DOWNLOAD = 'download';
    case EMAIL = 'email';
    case STORAGE = 'storage';
    case PRINTER = 'printer';
    case WEBHOOK = 'webhook';
    case DOCUMENT_LIBRARY = 'document_library';
}
```

**Methods:**

```php
// Check if requires rate limiting
public function requiresRateLimit(): bool

// Check if requires authentication
public function requiresAuth(): bool

// Check if synchronous operation
public function isSynchronous(): bool
```

**Example:**

```php
$destination = ExportDestination::EMAIL;

echo $destination->requiresRateLimit() ? 'Yes' : 'No';  // "Yes"
echo $destination->requiresAuth() ? 'Yes' : 'No';       // "Yes"
echo $destination->isSynchronous() ? 'Yes' : 'No';      // "No"
```

---

## Exceptions

### ExportException

**Purpose:** Base exception for all export errors.

**Namespace:** `Nexus\Export\Exceptions`

```php
abstract class ExportException extends \Exception
{
    // Base exception - all export exceptions extend this
}
```

---

### FormatterException

**Purpose:** Thrown when formatter fails.

**Namespace:** `Nexus\Export\Exceptions`

```php
final class FormatterException extends ExportException
{
    public static function formatFailed(
        ExportFormat $format,
        string $reason
    ): self {
        return new self(
            "Formatter for {$format->value} failed: {$reason}"
        );
    }
}
```

---

### UnsupportedFormatException

**Purpose:** Thrown when requested format has no registered formatter.

**Namespace:** `Nexus\Export\Exceptions`

```php
final class UnsupportedFormatException extends ExportException
{
    public static function noFormatterRegistered(
        ExportFormat $format
    ): self {
        return new self(
            "No formatter registered for format: {$format->value}"
        );
    }
}
```

---

### ValidationException

**Purpose:** Thrown when ExportDefinition fails schema validation.

**Namespace:** `Nexus\Export\Exceptions`

```php
final class ValidationException extends ExportException
{
    public static function invalidDefinition(
        array $errors
    ): self {
        $message = "ExportDefinition validation failed:\n" 
                 . implode("\n", $errors);
        return new self($message);
    }
}
```

**Example:**

```php
try {
    $result = $exportManager->export($definition, ExportFormat::CSV);
} catch (ValidationException $e) {
    echo "Validation failed:\n";
    foreach ($validator->getErrors() as $error) {
        echo "- {$error}\n";
    }
} catch (UnsupportedFormatException $e) {
    echo "Format not supported: {$e->getMessage()}\n";
} catch (FormatterException $e) {
    echo "Formatter error: {$e->getMessage()}\n";
}
```

---

## Integration Patterns

### Pattern 1: Domain Package Integration

```php
// In Nexus\Receivable
namespace Nexus\Receivable\Services;

use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\Export\ValueObjects\ExportDefinition;

class InvoiceExportService implements ExportGeneratorInterface
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository
    ) {}
    
    public function toExportDefinition(string $invoiceId): ExportDefinition
    {
        $invoice = $this->repository->findById($invoiceId);
        
        return new ExportDefinition(
            metadata: new ExportMetadata(...),
            structure: $this->buildInvoiceStructure($invoice)
        );
    }
    
    public function supportsSchemaVersion(): string
    {
        return '1.0';
    }
}
```

### Pattern 2: Custom Formatter

```php
// In application layer
namespace App\Services\Export;

use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportFormat;

final readonly class PdfFormatter implements ExportFormatterInterface
{
    public function __construct(
        private \TCPDF $tcpdf
    ) {}
    
    public function format(ExportDefinition $definition): string
    {
        // Generate PDF using TCPDF
        $this->tcpdf->AddPage();
        // ... render content
        
        return $this->tcpdf->Output('', 'S');  // Return as string
    }
    
    public function getFormat(): ExportFormat
    {
        return ExportFormat::PDF;
    }
    
    // ... other methods
}
```

---

**Last Updated:** 2025-11-24  
**Package Version:** 1.0.0  
**Schema Version:** 1.0  
**Maintained By:** Nexus Architecture Team
