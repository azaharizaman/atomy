# API Reference: Import

**Package:** `Nexus\Import`  
**Version:** 1.0.0

---

## Table of Contents

1. [Interfaces](#interfaces)
2. [Services](#services)
3. [Value Objects](#value-objects)
4. [Enums](#enums)
5. [Exceptions](#exceptions)
6. [Core Engine](#core-engine)
7. [Parsers](#parsers)

---

## Interfaces

### ImportProcessorInterface

Main pipeline orchestration interface.

```php
namespace Nexus\Import\Contracts;

interface ImportProcessorInterface
{
    /**
     * Process import with specified strategy
     *
     * @param ImportDefinition $definition Parsed import data
     * @param ImportHandlerInterface $handler Domain persistence handler
     * @param FieldMapping[] $mappings Field mapping configurations
     * @param ImportMode $mode Import mode (CREATE, UPDATE, UPSERT, DELETE, SYNC)
     * @param ImportStrategy $strategy Transaction strategy
     * @param TransactionManagerInterface|null $transactionManager Required for TRANSACTIONAL/BATCH
     * @param ValidationRule[] $validationRules Optional validation rules
     * @param int $batchSize Batch size for BATCH strategy (default: 500)
     * @return ImportResult Execution summary with success/failed counts and errors
     */
    public function process(
        ImportDefinition $definition,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode,
        ImportStrategy $strategy,
        ?TransactionManagerInterface $transactionManager = null,
        array $validationRules = [],
        int $batchSize = 500
    ): ImportResult;
}
```

---

### ImportHandlerInterface

Domain-specific persistence logic interface (implemented by consuming application).

```php
namespace Nexus\Import\Contracts;

interface ImportHandlerInterface
{
    /**
     * Handle import row with specified mode
     *
     * @param array $data Mapped and validated data
     * @param ImportMode $mode Import mode
     * @throws \Exception on persistence failure
     */
    public function handle(array $data, ImportMode $mode): void;

    /**
     * Get unique key fields for duplicate detection
     *
     * @return string[] e.g., ['email'] or ['company_id', 'employee_code']
     */
    public function getUniqueKeyFields(): array;

    /**
     * Get required fields for this import
     *
     * @return string[] Field names that must be present
     */
    public function getRequiredFields(): array;

    /**
     * Check if handler supports a mode
     *
     * @param ImportMode $mode Mode to check
     * @return bool True if supported
     */
    public function supportsMode(ImportMode $mode): bool;

    /**
     * Check if record exists with unique data
     *
     * @param array $uniqueData Data for unique key fields
     * @return bool True if exists
     */
    public function exists(array $uniqueData): bool;

    /**
     * Validate data against domain rules
     *
     * @param array $data Data to validate
     * @return string[] Error messages (empty if valid)
     */
    public function validateData(array $data): array;
}
```

---

### ImportParserInterface

File parsing abstraction interface.

```php
namespace Nexus\Import\Contracts;

interface ImportParserInterface
{
    /**
     * Parse file to ImportDefinition
     *
     * @param string $filePath Absolute path to file
     * @param ImportMetadata $metadata File context
     * @return ImportDefinition Intermediate representation (headers + rows)
     * @throws ParserException on parsing failure
     */
    public function parse(string $filePath, ImportMetadata $metadata): ImportDefinition;

    /**
     * Check if parser supports format
     *
     * @param ImportFormat $format Format to check
     * @return bool True if supported
     */
    public function supports(ImportFormat $format): bool;
}
```

---

### TransactionManagerInterface

Transaction lifecycle management interface (implemented by consuming application).

```php
namespace Nexus\Import\Contracts;

interface TransactionManagerInterface
{
    /**
     * Begin a new transaction
     */
    public function begin(): void;

    /**
     * Commit current transaction
     */
    public function commit(): void;

    /**
     * Rollback current transaction
     */
    public function rollback(): void;

    /**
     * Create a savepoint
     *
     * @param string $name Savepoint name
     */
    public function savepoint(string $name): void;

    /**
     * Rollback to a savepoint
     *
     * @param string $name Savepoint name
     */
    public function rollbackToSavepoint(string $name): void;

    /**
     * Check if currently in a transaction
     *
     * @return bool True if in transaction
     */
    public function inTransaction(): bool;

    /**
     * Get current transaction nesting level
     *
     * @return int Nesting level (0 = no transaction)
     */
    public function getTransactionLevel(): int;
}
```

---

### TransformerInterface

Data transformation interface.

```php
namespace Nexus\Import\Contracts;

interface TransformerInterface
{
    /**
     * Transform value with rules
     *
     * @param mixed $value Value to transform
     * @param string[] $rules Transformation rules (e.g., ['trim', 'lower'])
     * @param int $rowNumber Row number (for error context)
     * @param string $fieldName Field name (for error context)
     * @return array{value: mixed, errors: ImportError[]}
     */
    public function transform(
        mixed $value,
        array $rules,
        int $rowNumber,
        string $fieldName
    ): array;
}
```

**Available Transformation Rules:**

| Rule | Description | Example |
|------|-------------|---------|
| `trim` | Remove leading/trailing whitespace | `"  hello  "` → `"hello"` |
| `upper` | Convert to uppercase | `"hello"` → `"HELLO"` |
| `lower` | Convert to lowercase | `"HELLO"` → `"hello"` |
| `capitalize` | Capitalize each word | `"hello world"` → `"Hello World"` |
| `slug` | Convert to URL-safe slug | `"Hello World!"` → `"hello-world"` |
| `to_bool` | Convert to boolean | `"yes"` → `true` |
| `to_int` | Convert to integer | `"42"` → `42` |
| `to_float` | Convert to float | `"3.14"` → `3.14` |
| `to_string` | Convert to string | `42` → `"42"` |
| `parse_date:Y-m-d` | Parse date with format | `"2024-11-25"` → `DateTime` |
| `date_format:Y-m-d H:i:s` | Format date to string | `DateTime` → `"2024-11-25 10:30:00"` |
| `default:N/A` | Fallback value if null/empty | `null` → `"N/A"` |
| `coalesce:val1,val2` | First non-null value | Returns first non-null |

---

### FieldMapperInterface

Field mapping with transformations interface.

```php
namespace Nexus\Import\Contracts;

interface FieldMapperInterface
{
    /**
     * Map row fields using mappings
     *
     * @param array $row Source row data
     * @param FieldMapping[] $mappings Field mapping configurations
     * @param int $rowNumber Row number (for error context)
     * @return array{data: array, errors: ImportError[]}
     */
    public function map(array $row, array $mappings, int $rowNumber): array;
}
```

---

### ImportValidatorInterface

Validation logic interface.

```php
namespace Nexus\Import\Contracts;

interface ImportValidatorInterface
{
    /**
     * Validate row data
     *
     * @param array $row Row data to validate
     * @param ValidationRule[] $rules Validation rules
     * @param int $rowNumber Row number (for error context)
     * @return ImportError[] Validation errors (empty if valid)
     */
    public function validateRow(array $row, array $rules, int $rowNumber): array;

    /**
     * Validate import definition structure
     *
     * @param ImportDefinition $definition Definition to validate
     * @param ImportHandlerInterface $handler Handler with requirements
     * @throws InvalidDefinitionException if structure is invalid
     */
    public function validateDefinition(
        ImportDefinition $definition,
        ImportHandlerInterface $handler
    ): void;
}
```

**Supported Validation Types:**

| Type | Description | Constraint |
|------|-------------|------------|
| `required` | Field must not be empty | - |
| `email` | Valid email format | - |
| `numeric` | Numeric value | - |
| `integer` | Integer value | - |
| `min` | Minimum value | `18` |
| `max` | Maximum value | `100` |
| `min_length` | Minimum string length | `3` |
| `max_length` | Maximum string length | `255` |
| `date` | Valid date | - |
| `boolean` | Boolean value | - |

---

### DuplicateDetectorInterface

Duplicate detection interface.

```php
namespace Nexus\Import\Contracts;

interface DuplicateDetectorInterface
{
    /**
     * Detect internal duplicates (within import file)
     *
     * @param array[] $rows All rows from import
     * @param string[] $uniqueKeyFields Fields that define uniqueness
     * @return ImportError[] Duplicate errors
     */
    public function detectInternal(array $rows, array $uniqueKeyFields): array;

    /**
     * Detect external duplicate (against existing data)
     *
     * @param array $row Row data
     * @param string[] $uniqueKeyFields Fields that define uniqueness
     * @param callable $existsCheck Callback that checks if record exists
     * @param int $rowNumber Row number (for error context)
     * @return ImportError|null Duplicate error if exists
     */
    public function detectExternal(
        array $row,
        array $uniqueKeyFields,
        callable $existsCheck,
        int $rowNumber
    ): ?ImportError;
}
```

---

## Services

### ImportManager

Main public API for import operations.

```php
namespace Nexus\Import\Services;

final class ImportManager
{
    public function __construct(
        private readonly ImportProcessorInterface $processor,
        private readonly ?ImportAuthorizerInterface $authorizer = null,
        private readonly ?ImportContextInterface $context = null,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Register a parser for a format
     */
    public function registerParser(ImportFormat $format, ImportParserInterface $parser): void;

    /**
     * Parse file to ImportDefinition
     *
     * @throws ParserException if parsing fails
     * @throws UnsupportedFormatException if no parser registered
     */
    public function parse(string $filePath, ImportFormat $format): ImportDefinition;

    /**
     * Execute import
     *
     * @param string $filePath Absolute path to import file
     * @param ImportFormat $format File format
     * @param ImportHandlerInterface $handler Domain persistence handler
     * @param FieldMapping[] $mappings Field mappings
     * @param ImportMode $mode Import mode
     * @param ImportStrategy $strategy Transaction strategy
     * @param TransactionManagerInterface|null $transactionManager Required for TRANSACTIONAL/BATCH
     * @param ValidationRule[] $validationRules Optional validation rules
     * @param int $batchSize Batch size for BATCH strategy
     * @return ImportResult Execution summary
     * @throws ImportAuthorizationException if not authorized
     * @throws UnsupportedFormatException if format not supported
     * @throws InvalidDefinitionException if definition invalid
     */
    public function import(
        string $filePath,
        ImportFormat $format,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode,
        ImportStrategy $strategy,
        ?TransactionManagerInterface $transactionManager = null,
        array $validationRules = [],
        int $batchSize = 500
    ): ImportResult;

    /**
     * Validate import without executing
     */
    public function validate(
        string $filePath,
        ImportFormat $format,
        ImportHandlerInterface $handler,
        array $mappings,
        array $validationRules = []
    ): ImportResult;
}
```

---

### ImportProcessor

Pipeline orchestration with transaction strategy enforcement.

```php
namespace Nexus\Import\Services;

final class ImportProcessor implements ImportProcessorInterface
{
    public function __construct(
        private readonly FieldMapperInterface $fieldMapper,
        private readonly ImportValidatorInterface $validator,
        private readonly DuplicateDetectorInterface $duplicateDetector,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function process(
        ImportDefinition $definition,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode,
        ImportStrategy $strategy,
        ?TransactionManagerInterface $transactionManager = null,
        array $validationRules = [],
        int $batchSize = 500
    ): ImportResult;
}
```

---

## Value Objects

### FieldMapping

Defines field mapping with transformations.

```php
namespace Nexus\Import\ValueObjects;

final readonly class FieldMapping
{
    public function __construct(
        public string $sourceField,
        public string $targetField,
        public bool $isRequired = false,
        public mixed $defaultValue = null,
        public array $transformations = []
    ) {}
}
```

**Example:**
```php
new FieldMapping(
    sourceField: 'customer_email',
    targetField: 'email',
    isRequired: true,
    transformations: ['trim', 'lower']
);
```

---

### ImportDefinition

Intermediate representation of parsed file.

```php
namespace Nexus\Import\ValueObjects;

final readonly class ImportDefinition
{
    public function __construct(
        public array $headers,
        public array $rows,
        public ImportMetadata $metadata
    ) {}

    public function getRowCount(): int;
}
```

---

### ImportResult

Execution summary with success/failed counts and errors.

```php
namespace Nexus\Import\ValueObjects;

final readonly class ImportResult
{
    public function __construct(
        public int $successCount,
        public int $failedCount,
        public int $skippedCount,
        public array $errors = []
    ) {}

    public function getTotalProcessed(): int;
    public function getSuccessRate(): float;
    public function getAllErrors(): array;
    public function getErrorCount(): int;
    public function getErrorCountBySeverity(): array; // ['WARNING' => 3, 'ERROR' => 12]
    public function getErrorsByField(): array; // ['email' => [ImportError, ...]]
    public function getErrorsByRow(): array; // [5 => [ImportError, ...]]
    public function hasErrors(): bool;
    public function hasCriticalErrors(): bool;
}
```

---

### ImportError

Row-level error with severity and context.

```php
namespace Nexus\Import\ValueObjects;

final readonly class ImportError
{
    public function __construct(
        public int $rowNumber,
        public ?string $field,
        public ErrorSeverity $severity,
        public string $message,
        public mixed $originalValue = null,
        public ?string $transformationRule = null
    ) {}
}
```

---

### ValidationRule

Validation rule definition.

```php
namespace Nexus\Import\ValueObjects;

final readonly class ValidationRule
{
    public function __construct(
        public string $field,
        public string $type,
        public string $message,
        public mixed $constraint = null
    ) {}
}
```

**Example:**
```php
new ValidationRule(
    field: 'email',
    type: 'email',
    message: 'Invalid email format'
);

new ValidationRule(
    field: 'age',
    type: 'min',
    message: 'Must be 18 or older',
    constraint: 18
);
```

---

## Enums

### ImportFormat

Supported import formats.

```php
namespace Nexus\Import\ValueObjects;

enum ImportFormat: string
{
    case CSV = 'csv';
    case JSON = 'json';
    case XML = 'xml';
    case EXCEL = 'excel';

    public function requiresExternalParser(): bool;
}
```

---

### ImportMode

Import modes defining persistence behavior.

```php
namespace Nexus\Import\ValueObjects;

enum ImportMode: string
{
    case CREATE = 'create';   // Insert new records only
    case UPDATE = 'update';   // Update existing records only
    case UPSERT = 'upsert';   // Insert or update
    case DELETE = 'delete';   // Delete existing records
    case SYNC = 'sync';       // Full synchronization
}
```

---

### ImportStrategy

Transaction strategies.

```php
namespace Nexus\Import\ValueObjects;

enum ImportStrategy: string
{
    case TRANSACTIONAL = 'transactional';  // All-or-nothing
    case BATCH = 'batch';                   // Partial success
    case STREAM = 'stream';                 // No transactions
}
```

---

### ErrorSeverity

Error severity levels.

```php
namespace Nexus\Import\ValueObjects;

enum ErrorSeverity: string
{
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
}
```

---

## Exceptions

All exceptions extend `Nexus\Import\Exceptions\ImportException`.

### ImportException

Base exception for all import errors.

### ParserException

Thrown when file parsing fails.

### ValidationException

Thrown when validation fails at definition level.

### TransformationException

Thrown for system-level transformation failures (not row-level).

### InvalidDefinitionException

Thrown when import definition structure is invalid.

### UnsupportedFormatException

Thrown when no parser registered for format.

### ImportAuthorizationException

Thrown when user not authorized to perform import.

---

## Core Engine

### DataTransformer

Implements `TransformerInterface` with 13 built-in transformation rules.

**Location:** `src/Core/Engine/DataTransformer.php`

### FieldMapper

Orchestrates field mapping and transformations.

**Location:** `src/Core/Engine/FieldMapper.php`

### DefinitionValidator

Validates import definitions and row data.

**Location:** `src/Core/Engine/DefinitionValidator.php`

### DuplicateDetector

Detects duplicates using xxh128 hash-based algorithm.

**Location:** `src/Core/Engine/DuplicateDetector.php`

### ErrorCollector

Aggregates errors by row, field, and severity.

**Location:** `src/Core/Engine/ErrorCollector.php`

### BatchProcessor

Memory-efficient batch processing.

**Location:** `src/Core/Engine/BatchProcessor.php`

---

## Parsers

### CsvParser

RFC 4180 compliant CSV parser with streaming support.

**Location:** `src/Parsers/CsvParser.php`

**Supports:** `ImportFormat::CSV`

### JsonParser

JSON array parser.

**Location:** `src/Parsers/JsonParser.php`

**Supports:** `ImportFormat::JSON`

### XmlParser

XML document parser with attribute handling.

**Location:** `src/Parsers/XmlParser.php`

**Supports:** `ImportFormat::XML`

---

**Last Updated:** 2024-11-25  
**Package Version:** 1.0.0
