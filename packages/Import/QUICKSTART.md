# Nexus\Import Quick Start Guide

**Get started with data imports in 5 minutes.**

---

## 1. Install Package

```bash
# In monorepo root
composer require nexus/import:"*@dev"
```

---

## 2. Create Import Handler

```php
// apps/Atomy/app/Import/Handlers/CustomerImportHandler.php

namespace App\Import\Handlers;

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
            ImportMode::UPDATE => $this->repository->updateByEmail($data),
            ImportMode::UPSERT => $this->repository->upsert($data),
            default => throw new \RuntimeException("Unsupported mode: {$mode->name}")
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
        return in_array($mode, [ImportMode::CREATE, ImportMode::UPDATE, ImportMode::UPSERT]);
    }

    public function exists(array $uniqueData): bool
    {
        return $this->repository->existsByEmail($uniqueData['email']);
    }

    public function validateData(array $data): array
    {
        // Add custom domain validation if needed
        return [];
    }
}
```

---

## 3. Create Service Provider

```php
// apps/Atomy/app/Providers/ImportServiceProvider.php

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
        // Core engine
        $this->app->singleton(DataTransformer::class);
        
        $this->app->bind(FieldMapperInterface::class, function ($app) {
            return new FieldMapper($app->make(DataTransformer::class));
        });
        
        $this->app->bind(ImportValidatorInterface::class, DefinitionValidator::class);
        $this->app->bind(DuplicateDetectorInterface::class, DuplicateDetector::class);
        
        // Laravel implementations
        $this->app->singleton(
            TransactionManagerInterface::class,
            LaravelTransactionManager::class
        );
        
        // Processor
        $this->app->singleton(ImportProcessor::class, function ($app) {
            return new ImportProcessor(
                fieldMapper: $app->make(FieldMapperInterface::class),
                validator: $app->make(ImportValidatorInterface::class),
                duplicateDetector: $app->make(DuplicateDetectorInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });
        
        // Manager with parsers
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

**Register in `config/app.php`**:
```php
'providers' => [
    // ...
    App\Providers\ImportServiceProvider::class,
],
```

---

## 4. Create Supporting Classes

### Excel Parser
```php
// apps/Atomy/app/Services/Import/ExcelParser.php

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
                $data = [];
                foreach ($row->getCellIterator() as $cell) {
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
            throw new ParserException("Excel parse failed: {$e->getMessage()}", previous: $e);
        }
    }

    public function supports(ImportFormat $format): bool
    {
        return $format === ImportFormat::EXCEL;
    }
}
```

### Transaction Manager
```php
// apps/Atomy/app/Services/Import/LaravelTransactionManager.php

namespace App\Services\Import;

use Nexus\Import\Contracts\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionManager implements TransactionManagerInterface
{
    public function begin(): void { DB::beginTransaction(); }
    public function commit(): void { DB::commit(); }
    public function rollback(): void { DB::rollBack(); }
    
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

---

## 5. Use in Your Code

```php
use Nexus\Import\Services\ImportManager;
use Nexus\Import\ValueObjects\{
    ImportFormat,
    ImportMode,
    ImportStrategy,
    FieldMapping,
    ValidationRule
};
use App\Import\Handlers\CustomerImportHandler;

// Inject ImportManager
public function __construct(
    private readonly ImportManager $importManager,
    private readonly CustomerImportHandler $customerHandler
) {}

public function importCustomers(string $filePath): void
{
    // Define field mappings
    $mappings = [
        new FieldMapping(
            sourceField: 'Full Name',
            targetField: 'name',
            required: true,
            transformations: ['trim', 'capitalize']
        ),
        new FieldMapping(
            sourceField: 'Email Address',
            targetField: 'email',
            required: true,
            transformations: ['trim', 'lower']
        ),
        new FieldMapping(
            sourceField: 'Phone',
            targetField: 'phone',
            required: false,
            defaultValue: 'N/A'
        ),
    ];

    // Define validation rules
    $validationRules = [
        new ValidationRule('email', 'email', 'Invalid email format'),
        new ValidationRule('name', 'required', 'Name is required'),
        new ValidationRule('name', 'max_length', 'Name too long', 255),
    ];

    // Import
    $result = $this->importManager->import(
        filePath: $filePath,
        format: ImportFormat::CSV,
        handler: $this->customerHandler,
        mappings: $mappings,
        mode: ImportMode::UPSERT,
        strategy: ImportStrategy::BATCH,
        transactionManager: app(TransactionManagerInterface::class),
        validationRules: $validationRules
    );

    // Report results
    echo "Success: {$result->successCount}\n";
    echo "Skipped: {$result->skippedCount}\n";
    echo "Success Rate: {$result->getSuccessRate()}%\n";

    if ($result->hasErrors()) {
        foreach ($result->getErrorsByField() as $field => $errors) {
            echo "Field '{$field}': " . count($errors) . " errors\n";
        }
    }
}
```

---

## 6. Example CSV File

**customers.csv**:
```csv
Full Name,Email Address,Phone
John Doe,john@example.com,555-1234
Jane Smith,jane@example.com,555-5678
Bob Wilson,bob@example.com,
```

---

## Common Patterns

### Pattern 1: Simple CREATE Import
```php
$result = $importManager->import(
    filePath: storage_path('imports/new_customers.csv'),
    format: ImportFormat::CSV,
    handler: $customerHandler,
    mappings: $mappings,
    mode: ImportMode::CREATE,  // Insert only
    strategy: ImportStrategy::BATCH
);
```

### Pattern 2: UPSERT with Transactions
```php
$result = $importManager->import(
    filePath: storage_path('imports/customers_sync.csv'),
    format: ImportFormat::CSV,
    handler: $customerHandler,
    mappings: $mappings,
    mode: ImportMode::UPSERT,  // Insert or update
    strategy: ImportStrategy::TRANSACTIONAL,  // All-or-nothing
    transactionManager: app(TransactionManagerInterface::class)
);
```

### Pattern 3: Large File STREAM Import
```php
$result = $importManager->import(
    filePath: storage_path('imports/huge_dataset.csv'),
    format: ImportFormat::CSV,
    handler: $handler,
    mappings: $mappings,
    mode: ImportMode::CREATE,
    strategy: ImportStrategy::STREAM  // Minimal memory
);
```

### Pattern 4: Excel Import
```php
$result = $importManager->import(
    filePath: storage_path('imports/customers.xlsx'),
    format: ImportFormat::EXCEL,  // Requires ExcelParser
    handler: $customerHandler,
    mappings: $mappings,
    mode: ImportMode::UPSERT,
    strategy: ImportStrategy::BATCH
);
```

---

## Transformation Examples

```php
// String transformations
new FieldMapping(
    sourceField: 'name',
    targetField: 'name',
    transformations: ['trim', 'capitalize']
);

// Date transformations
new FieldMapping(
    sourceField: 'signup_date',
    targetField: 'created_at',
    transformations: [
        'parse_date:m/d/Y',      // Parse "12/31/2024"
        'date_format:Y-m-d'      // Format to "2024-12-31"
    ]
);

// Type conversions
new FieldMapping(
    sourceField: 'is_active',
    targetField: 'active',
    transformations: ['to_bool']  // "yes" → true
);

// Multiple transformations (applied in order)
new FieldMapping(
    sourceField: 'email',
    targetField: 'email',
    transformations: ['trim', 'lower']
);
```

---

## Error Handling

```php
$result = $importManager->import(...);

if ($result->hasErrors()) {
    // By severity
    $counts = $result->getErrorCountBySeverity();
    echo "Warnings: {$counts['WARNING']}\n";
    echo "Errors: {$counts['ERROR']}\n";
    
    // By field
    foreach ($result->getErrorsByField() as $field => $errors) {
        echo "Field '{$field}' has " . count($errors) . " errors\n";
    }
    
    // By row
    foreach ($result->getErrorsByRow() as $rowNumber => $errors) {
        echo "Row {$rowNumber}:\n";
        foreach ($errors as $error) {
            echo "  - {$error->message}\n";
        }
    }
}
```

---

## Next Steps

1. **Read full documentation**: `packages/Import/README.md`
2. **Check implementation details**: `docs/IMPORT_IMPLEMENTATION_SUMMARY.md`
3. **Create custom transformations**: Register rules with `DataTransformer::registerRule()`
4. **Add custom validation**: Implement `ImportHandlerInterface::validateData()`
5. **Set up monitoring**: Log ImportResult metrics to analytics

---

**You're ready to import! 🚀**
