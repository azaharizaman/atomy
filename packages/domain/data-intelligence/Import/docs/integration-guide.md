# Integration Guide: Import

This guide demonstrates how to integrate the Nexus\Import package into different PHP frameworks and architectures.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Vanilla PHP Integration](#vanilla-php-integration)
4. [Dependency Injection Setup](#dependency-injection-setup)
5. [Common Patterns](#common-patterns)
6. [Error Handling](#error-handling)
7. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### 1. Install Package

```bash
composer require nexus/import:"*@dev"
```

### 2. Create Transaction Manager

Create `app/Services/Import/LaravelTransactionManager.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Import;

use Nexus\Import\Contracts\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionManager implements TransactionManagerInterface
{
    public function begin(): void
    {
        DB::beginTransaction();
    }

    public function commit(): void
    {
        DB::commit();
    }

    public function rollback(): void
    {
        DB::rollBack();
    }

    public function savepoint(string $name): void
    {
        DB::statement("SAVEPOINT {$name}");
    }

    public function rollbackToSavepoint(string $name): void
    {
        DB::statement("ROLLBACK TO SAVEPOINT {$name}");
    }

    public function inTransaction(): bool
    {
        return DB::transactionLevel() > 0;
    }

    public function getTransactionLevel(): int
    {
        return DB::transactionLevel();
    }
}
```

### 3. Create Excel Parser (Optional)

Create `app/Services/Import/ExcelParser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Import;

use Nexus\Import\Contracts\ImportParserInterface;
use Nexus\Import\ValueObjects\{ImportDefinition, ImportMetadata, ImportFormat};
use Nexus\Import\Exceptions\ParserException;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class ExcelParser implements ImportParserInterface
{
    public function parse(string $filePath, ImportMetadata $metadata): ImportDefinition
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $headers = [];
            $rows = [];
            
            foreach ($worksheet->getRowIterator() as $index => $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $data = [];
                foreach ($cellIterator as $cell) {
                    $data[] = $cell->getValue();
                }
                
                if ($index === 1) {
                    $headers = array_map('trim', $data);
                } else {
                    $rows[] = array_combine($headers, $data);
                }
            }
            
            return new ImportDefinition($headers, $rows, $metadata);
            
        } catch (\Throwable $e) {
            throw new ParserException(
                "Failed to parse Excel file: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function supports(ImportFormat $format): bool
    {
        return $format === ImportFormat::EXCEL;
    }
}
```

### 4. Create Service Provider

Create `app/Providers/ImportServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Nexus\Import\Services\{ImportManager, ImportProcessor};
use Nexus\Import\Contracts\{
    FieldMapperInterface,
    ImportValidatorInterface,
    DuplicateDetectorInterface,
    TransactionManagerInterface
};
use Nexus\Import\Core\Engine\{
    DataTransformer,
    FieldMapper,
    DefinitionValidator,
    DuplicateDetector
};
use Nexus\Import\Parsers\{CsvParser, JsonParser, XmlParser};
use Nexus\Import\ValueObjects\ImportFormat;
use App\Services\Import\{ExcelParser, LaravelTransactionManager};

final class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Core components
        $this->app->singleton(DataTransformer::class);
        
        $this->app->bind(FieldMapperInterface::class, function ($app) {
            return new FieldMapper($app->make(DataTransformer::class));
        });
        
        $this->app->bind(ImportValidatorInterface::class, DefinitionValidator::class);
        $this->app->bind(DuplicateDetectorInterface::class, DuplicateDetector::class);
        $this->app->singleton(TransactionManagerInterface::class, LaravelTransactionManager::class);
        
        // ImportProcessor
        $this->app->singleton(ImportProcessor::class, function ($app) {
            return new ImportProcessor(
                fieldMapper: $app->make(FieldMapperInterface::class),
                validator: $app->make(ImportValidatorInterface::class),
                duplicateDetector: $app->make(DuplicateDetectorInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });
        
        // ImportManager with parsers
        $this->app->singleton(ImportManager::class, function ($app) {
            $manager = new ImportManager(
                processor: $app->make(ImportProcessor::class),
                authorizer: null,
                context: null,
                logger: $app->make(LoggerInterface::class)
            );
            
            $manager->registerParser(ImportFormat::CSV, new CsvParser());
            $manager->registerParser(ImportFormat::JSON, new JsonParser());
            $manager->registerParser(ImportFormat::XML, new XmlParser());
            $manager->registerParser(ImportFormat::EXCEL, new ExcelParser());
            
            return $manager;
        });
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\ImportServiceProvider::class,
],
```

### 5. Create Import Handler

```php
<?php

declare(strict_types=1);

namespace App\Services\Import;

use Nexus\Import\Contracts\ImportHandlerInterface;
use Nexus\Import\ValueObjects\ImportMode;
use App\Models\Customer;

final readonly class CustomerImportHandler implements ImportHandlerInterface
{
    public function handle(array $data, ImportMode $mode): void
    {
        match($mode) {
            ImportMode::CREATE => Customer::create($data),
            ImportMode::UPDATE => Customer::where('email', $data['email'])->update($data),
            ImportMode::UPSERT => Customer::updateOrCreate(
                ['email' => $data['email']],
                $data
            ),
            ImportMode::DELETE => Customer::where('email', $data['email'])->delete(),
            ImportMode::SYNC => throw new \LogicException('SYNC not implemented')
        };
    }

    public function getUniqueKeyFields(): array
    {
        return ['email'];
    }

    public function getRequiredFields(): array
    {
        return ['name', 'email'];
    }

    public function supportsMode(ImportMode $mode): bool
    {
        return $mode !== ImportMode::SYNC;
    }

    public function exists(array $uniqueData): bool
    {
        return Customer::where('email', $uniqueData['email'])->exists();
    }

    public function validateData(array $data): array
    {
        $errors = [];
        
        if (isset($data['age']) && $data['age'] < 18) {
            $errors[] = 'Customer must be 18 or older';
        }
        
        return $errors;
    }
}
```

### 6. Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\Import\Services\ImportManager;
use Nexus\Import\ValueObjects\{ImportFormat, ImportMode, ImportStrategy, FieldMapping};
use Nexus\Import\Contracts\TransactionManagerInterface;
use App\Services\Import\CustomerImportHandler;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportManager $importManager,
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function importCustomers(Request $request)
    {
        $file = $request->file('import_file');
        $filePath = $file->storeAs('imports', $file->getClientOriginalName());

        $mappings = [
            new FieldMapping('name', 'name', true, transformations: ['trim', 'capitalize']),
            new FieldMapping('email', 'email', true, transformations: ['trim', 'lower']),
            new FieldMapping('phone', 'phone', false, defaultValue: 'N/A'),
        ];

        $result = $this->importManager->import(
            filePath: storage_path('app/' . $filePath),
            format: ImportFormat::CSV,
            handler: new CustomerImportHandler(),
            mappings: $mappings,
            mode: ImportMode::UPSERT,
            strategy: ImportStrategy::BATCH,
            transactionManager: $this->transactionManager
        );

        return response()->json([
            'success' => $result->successCount,
            'failed' => $result->failedCount,
            'skipped' => $result->skippedCount,
            'success_rate' => $result->getSuccessRate(),
            'errors' => $result->getErrorsByField()
        ]);
    }
}
```

---

## Symfony Integration

### 1. Create Transaction Manager

```php
<?php

declare(strict_types=1);

namespace App\Service\Import;

use Nexus\Import\Contracts\TransactionManagerInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private Connection $connection
    ) {}

    public function begin(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    public function savepoint(string $name): void
    {
        $this->connection->createSavepoint($name);
    }

    public function rollbackToSavepoint(string $name): void
    {
        $this->connection->rollbackSavepoint($name);
    }

    public function inTransaction(): bool
    {
        return $this->connection->isTransactionActive();
    }

    public function getTransactionLevel(): int
    {
        return $this->connection->getTransactionNestingLevel();
    }
}
```

### 2. Register Services in services.yaml

```yaml
services:
    # Core components
    Nexus\Import\Core\Engine\DataTransformer: ~
    
    Nexus\Import\Contracts\FieldMapperInterface:
        class: Nexus\Import\Core\Engine\FieldMapper
        arguments:
            - '@Nexus\Import\Core\Engine\DataTransformer'
    
    Nexus\Import\Contracts\ImportValidatorInterface:
        class: Nexus\Import\Core\Engine\DefinitionValidator
    
    Nexus\Import\Contracts\DuplicateDetectorInterface:
        class: Nexus\Import\Core\Engine\DuplicateDetector
    
    Nexus\Import\Contracts\TransactionManagerInterface:
        class: App\Service\Import\DoctrineTransactionManager
        arguments:
            - '@doctrine.dbal.default_connection'
    
    # ImportProcessor
    Nexus\Import\Services\ImportProcessor:
        arguments:
            - '@Nexus\Import\Contracts\FieldMapperInterface'
            - '@Nexus\Import\Contracts\ImportValidatorInterface'
            - '@Nexus\Import\Contracts\DuplicateDetectorInterface'
            - '@logger'
    
    # ImportManager
    Nexus\Import\Services\ImportManager:
        arguments:
            - '@Nexus\Import\Services\ImportProcessor'
            - null  # authorizer
            - null  # context
            - '@logger'
        calls:
            - registerParser: [!php/const Nexus\Import\ValueObjects\ImportFormat::CSV, '@App\Service\Import\CsvParser']
            - registerParser: [!php/const Nexus\Import\ValueObjects\ImportFormat::JSON, '@App\Service\Import\JsonParser']
```

---

## Vanilla PHP Integration

```php
<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Nexus\Import\Services\{ImportManager, ImportProcessor};
use Nexus\Import\Core\Engine\{DataTransformer, FieldMapper, DefinitionValidator, DuplicateDetector};
use Nexus\Import\Parsers\CsvParser;
use Nexus\Import\ValueObjects\{ImportFormat, ImportMode, ImportStrategy, FieldMapping};

// Create dependencies
$dataTransformer = new DataTransformer();
$fieldMapper = new FieldMapper($dataTransformer);
$validator = new DefinitionValidator();
$duplicateDetector = new DuplicateDetector();
$logger = new \Psr\Log\NullLogger();

// Create processor
$processor = new ImportProcessor(
    fieldMapper: $fieldMapper,
    validator: $validator,
    duplicateDetector: $duplicateDetector,
    logger: $logger
);

// Create manager
$manager = new ImportManager(
    processor: $processor,
    authorizer: null,
    context: null,
    logger: $logger
);

// Register parsers
$manager->registerParser(ImportFormat::CSV, new CsvParser());

// Define mappings
$mappings = [
    new FieldMapping('name', 'name', true),
    new FieldMapping('email', 'email', true, transformations: ['trim', 'lower']),
];

// Execute import
$result = $manager->import(
    filePath: '/path/to/customers.csv',
    format: ImportFormat::CSV,
    handler: new YourImportHandler(),
    mappings: $mappings,
    mode: ImportMode::CREATE,
    strategy: ImportStrategy::STREAM  // No transactions
);

echo "Imported: {$result->successCount}\n";
echo "Failed: {$result->failedCount}\n";
```

---

## Common Patterns

### Pattern 1: Validation with Error Reporting

```php
$validationRules = [
    new ValidationRule('email', 'email', 'Invalid email'),
    new ValidationRule('email', 'required', 'Email required'),
    new ValidationRule('age', 'min', 'Must be 18+', 18),
];

$result = $importManager->import(
    filePath: $filePath,
    format: ImportFormat::CSV,
    handler: $handler,
    mappings: $mappings,
    mode: ImportMode::CREATE,
    strategy: ImportStrategy::BATCH,
    transactionManager: $transactionManager,
    validationRules: $validationRules
);

if ($result->hasErrors()) {
    // Group by severity
    $errorCounts = $result->getErrorCountBySeverity();
    logger()->warning("Import warnings: {$errorCounts['WARNING']}");
    logger()->error("Import errors: {$errorCounts['ERROR']}");
    
    // Group by field
    foreach ($result->getErrorsByField() as $field => $errors) {
        logger()->error("Field '{$field}' errors: " . count($errors));
    }
}
```

### Pattern 2: Dry Run (Validation Only)

```php
$result = $importManager->validate(
    filePath: $filePath,
    format: ImportFormat::CSV,
    handler: $handler,
    mappings: $mappings,
    validationRules: $validationRules
);

// No data persisted, only validation performed
if ($result->hasErrors()) {
    // Show user what errors would occur
}
```

### Pattern 3: Progress Tracking (Custom Implementation)

```php
// Create custom handler with progress callback
final class ProgressTrackingHandler implements ImportHandlerInterface
{
    private int $processed = 0;
    
    public function __construct(
        private ImportHandlerInterface $innerHandler,
        private \Closure $progressCallback
    ) {}
    
    public function handle(array $data, ImportMode $mode): void
    {
        $this->innerHandler->handle($data, $mode);
        $this->processed++;
        ($this->progressCallback)($this->processed);
    }
    
    // ... delegate other methods to innerHandler
}

// Use with progress tracking
$handler = new ProgressTrackingHandler(
    new CustomerImportHandler(),
    function (int $processed) {
        echo "Processed: {$processed}\r";
        flush();
    }
);
```

---

## Error Handling

### Comprehensive Error Handling

```php
try {
    $result = $importManager->import(
        // ... parameters
    );
    
    if ($result->hasCriticalErrors()) {
        // Critical errors require immediate attention
        foreach ($result->getAllErrors() as $error) {
            if ($error->severity === ErrorSeverity::CRITICAL) {
                logger()->critical("Row {$error->rowNumber}: {$error->message}");
            }
        }
        
        throw new \RuntimeException('Import failed with critical errors');
    }
    
    if ($result->hasErrors()) {
        // Non-critical errors - log but continue
        logger()->warning("Import completed with {$result->getErrorCount()} errors");
    }
    
} catch (UnsupportedFormatException $e) {
    // Format not supported
    logger()->error("Import failed: Unsupported format");
    
} catch (ParserException $e) {
    // File parsing failed
    logger()->error("Import failed: {$e->getMessage()}");
    
} catch (InvalidDefinitionException $e) {
    // Definition structure invalid
    logger()->error("Import failed: Invalid structure - {$e->getMessage()}");
    
} catch (ImportAuthorizationException $e) {
    // User not authorized
    logger()->error("Import failed: Unauthorized");
    
} catch (\Throwable $e) {
    // Unexpected error
    logger()->error("Import failed: {$e->getMessage()}");
}
```

---

## Troubleshooting

### Issue: "Parser not registered for format EXCEL"

**Cause:** Excel parser not registered in ImportManager

**Solution:**
```php
$manager->registerParser(ImportFormat::EXCEL, new ExcelParser());
```

### Issue: "TransactionManager required for TRANSACTIONAL strategy"

**Cause:** TRANSACTIONAL/BATCH strategy requires TransactionManager

**Solution:**
```php
$result = $importManager->import(
    // ...
    strategy: ImportStrategy::TRANSACTIONAL,
    transactionManager: $transactionManager  // Don't forget!
);
```

### Issue: Memory exhausted on large imports

**Cause:** Using TRANSACTIONAL strategy on large file

**Solution:** Use STREAM strategy for large files
```php
strategy: ImportStrategy::STREAM  // No transactions, minimal memory
```

### Issue: Import succeeds but no data in database

**Cause:** Handler not actually persisting data

**Solution:** Check handler implementation
```php
public function handle(array $data, ImportMode $mode): void
{
    // WRONG: No actual persistence
    logger()->info('Would import', $data);
    
    // CORRECT: Actually persist
    Customer::create($data);
}
```

### Issue: Duplicate detection not working

**Cause:** Handler's `exists()` method not implemented correctly

**Solution:**
```php
public function exists(array $uniqueData): bool
{
    // CORRECT: Actually check database
    return Customer::where('email', $uniqueData['email'])->exists();
}
```

---

**Last Updated:** 2024-11-25
