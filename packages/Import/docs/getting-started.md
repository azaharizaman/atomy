# Getting Started with Nexus Import

## Prerequisites

- PHP 8.3 or higher
- Composer

## Installation

```bash
composer require nexus/import:"*@dev"
```

## Basic Configuration

The Import package is framework-agnostic and requires no configuration out of the box. All dependencies are injected via interfaces.

### Framework Integration

#### Laravel Service Provider

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
        // Bind core components
        $this->app->singleton(DataTransformer::class);
        
        $this->app->bind(FieldMapperInterface::class, function ($app) {
            return new FieldMapper($app->make(DataTransformer::class));
        });
        
        $this->app->bind(ImportValidatorInterface::class, DefinitionValidator::class);
        $this->app->bind(DuplicateDetectorInterface::class, DuplicateDetector::class);
        $this->app->singleton(TransactionManagerInterface::class, LaravelTransactionManager::class);
        
        // Bind ImportProcessor
        $this->app->singleton(ImportProcessor::class, function ($app) {
            return new ImportProcessor(
                fieldMapper: $app->make(FieldMapperInterface::class),
                validator: $app->make(ImportValidatorInterface::class),
                duplicateDetector: $app->make(DuplicateDetectorInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });
        
        // Bind ImportManager with parsers
        $this->app->singleton(ImportManager::class, function ($app) {
            $manager = new ImportManager(
                processor: $app->make(ImportProcessor::class),
                authorizer: null,
                context: null,
                logger: $app->make(LoggerInterface::class)
            );
            
            // Register parsers
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

## Your First Import

### Step 1: Create an Import Handler

Create `app/Services/Import/CustomerImportHandler.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Import;

use Nexus\Import\Contracts\ImportHandlerInterface;
use Nexus\Import\ValueObjects\ImportMode;
use App\Repositories\CustomerRepository;

final readonly class CustomerImportHandler implements ImportHandlerInterface
{
    public function __construct(
        private CustomerRepository $repository
    ) {}

    public function handle(array $data, ImportMode $mode): void
    {
        match($mode) {
            ImportMode::CREATE => $this->repository->create($data),
            ImportMode::UPDATE => $this->repository->update($data),
            ImportMode::UPSERT => $this->repository->upsert($data),
            ImportMode::DELETE => $this->repository->delete($data),
            ImportMode::SYNC => $this->repository->sync($data)
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
        return $mode !== ImportMode::DELETE; // Don't allow deletions
    }

    public function exists(array $uniqueData): bool
    {
        return $this->repository->existsByEmail($uniqueData['email']);
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

### Step 2: Define Field Mappings

```php
use Nexus\Import\ValueObjects\FieldMapping;

$mappings = [
    new FieldMapping(
        sourceField: 'customer_name',
        targetField: 'name',
        isRequired: true,
        transformations: ['trim', 'capitalize']
    ),
    new FieldMapping(
        sourceField: 'customer_email',
        targetField: 'email',
        isRequired: true,
        transformations: ['trim', 'lower']
    ),
    new FieldMapping(
        sourceField: 'phone',
        targetField: 'phone_number',
        isRequired: false,
        defaultValue: 'N/A'
    ),
];
```

### Step 3: Execute Import

```php
use Nexus\Import\Services\ImportManager;
use Nexus\Import\ValueObjects\{ImportFormat, ImportMode, ImportStrategy};
use Nexus\Import\Contracts\TransactionManagerInterface;

$importManager = app(ImportManager::class);

$result = $importManager->import(
    filePath: storage_path('imports/customers.csv'),
    format: ImportFormat::CSV,
    handler: new CustomerImportHandler($customerRepository),
    mappings: $mappings,
    mode: ImportMode::UPSERT,
    strategy: ImportStrategy::BATCH,
    transactionManager: app(TransactionManagerInterface::class)
);

// View results
echo "Imported: {$result->successCount} customers\n";
echo "Skipped: {$result->skippedCount} rows\n";
echo "Success Rate: {$result->getSuccessRate()}%\n";

if ($result->hasErrors()) {
    foreach ($result->getErrorsByField() as $field => $errors) {
        echo "Field '{$field}': " . count($errors) . " errors\n";
    }
}
```

## Transaction Strategies

### TRANSACTIONAL (All-or-Nothing)

```php
$result = $importManager->import(
    filePath: $filePath,
    format: ImportFormat::CSV,
    handler: $handler,
    mappings: $mappings,
    mode: ImportMode::CREATE,
    strategy: ImportStrategy::TRANSACTIONAL,
    transactionManager: $transactionManager
);

// If any critical error occurs, ALL changes are rolled back
```

**Use Case:** Financial data, critical imports requiring data integrity

### BATCH (Partial Success)

```php
$result = $importManager->import(
    filePath: $filePath,
    format: ImportFormat::CSV,
    handler: $handler,
    mappings: $mappings,
    mode: ImportMode::UPSERT,
    strategy: ImportStrategy::BATCH,
    transactionManager: $transactionManager,
    batchSize: 500  // Process in chunks of 500 rows
);

// Failed batches are skipped, successful batches are committed
```

**Use Case:** Large imports where partial success is acceptable

### STREAM (Minimal Memory)

```php
$result = $importManager->import(
    filePath: $filePath,
    format: ImportFormat::CSV,
    handler: $handler,
    mappings: $mappings,
    mode: ImportMode::CREATE,
    strategy: ImportStrategy::STREAM
    // No transaction manager needed
);

// Row-by-row processing, minimal memory footprint
```

**Use Case:** Very large files (millions of rows)

## Next Steps

- **Validation Rules:** [Learn about validation rules](api-reference.md#validation)
- **Transformations:** [See all 13 transformation rules](api-reference.md#transformations)
- **Error Handling:** [Advanced error handling patterns](integration-guide.md#error-handling)
- **Examples:** [More code examples](examples/)

## Common Issues

### "Parser not registered for format"

**Solution:** Ensure you've registered the parser in your service provider:

```php
$manager->registerParser(ImportFormat::CSV, new CsvParser());
```

### "TransactionManager required for TRANSACTIONAL strategy"

**Solution:** Always pass a TransactionManager when using TRANSACTIONAL or BATCH strategy:

```php
$result = $importManager->import(
    // ... other parameters
    strategy: ImportStrategy::TRANSACTIONAL,
    transactionManager: app(TransactionManagerInterface::class)  // Don't forget this!
);
```

### Memory exhausted on large imports

**Solution:** Use STREAM strategy for very large files:

```php
strategy: ImportStrategy::STREAM  // Processes row-by-row
```
