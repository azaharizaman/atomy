<?php

declare(strict_types=1);

/**
 * Advanced Import Example: Product Import with Validation and Error Handling
 *
 * This example demonstrates:
 * - Custom validation rules
 * - Transformation pipeline
 * - Duplicate detection
 * - Comprehensive error handling
 * - Multiple transaction strategies
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Import\Services\ImportManager;
use Nexus\Import\ValueObjects\{
    ImportFormat,
    ImportMode,
    ImportStrategy,
    FieldMapping,
    ValidationRule,
    ErrorSeverity
};
use Nexus\Import\Contracts\{
    ImportHandlerInterface,
    TransactionManagerInterface
};

$importManager = app(ImportManager::class);
$transactionManager = app(TransactionManagerInterface::class);

// ============================================================================
// Example 1: Complex Field Mappings with Transformations
// ============================================================================

$mappings = [
    // Product name: trim, capitalize
    new FieldMapping(
        sourceField: 'product_name',
        targetField: 'name',
        isRequired: true,
        transformations: ['trim', 'capitalize']
    ),
    
    // SKU: trim, uppercase, slug (for URL-safe SKU)
    new FieldMapping(
        sourceField: 'sku',
        targetField: 'sku',
        isRequired: true,
        transformations: ['trim', 'upper', 'slug']
    ),
    
    // Price: convert to float
    new FieldMapping(
        sourceField: 'price',
        targetField: 'price',
        isRequired: true,
        transformations: ['to_float']
    ),
    
    // Stock: convert to integer
    new FieldMapping(
        sourceField: 'stock',
        targetField: 'stock_quantity',
        isRequired: false,
        defaultValue: 0,
        transformations: ['to_int']
    ),
    
    // Active status: convert to boolean
    new FieldMapping(
        sourceField: 'is_active',
        targetField: 'active',
        isRequired: false,
        defaultValue: true,
        transformations: ['to_bool']
    ),
    
    // Created date: parse with format
    new FieldMapping(
        sourceField: 'created_date',
        targetField: 'created_at',
        isRequired: false,
        transformations: ['parse_date:Y-m-d']
    ),
];

// ============================================================================
// Example 2: Validation Rules
// ============================================================================

$validationRules = [
    // Name validation
    new ValidationRule(
        field: 'name',
        type: 'required',
        message: 'Product name is required'
    ),
    new ValidationRule(
        field: 'name',
        type: 'max_length',
        message: 'Product name too long (max 255 characters)',
        constraint: 255
    ),
    
    // SKU validation
    new ValidationRule(
        field: 'sku',
        type: 'required',
        message: 'SKU is required'
    ),
    new ValidationRule(
        field: 'sku',
        type: 'max_length',
        message: 'SKU too long (max 50 characters)',
        constraint: 50
    ),
    
    // Price validation
    new ValidationRule(
        field: 'price',
        type: 'required',
        message: 'Price is required'
    ),
    new ValidationRule(
        field: 'price',
        type: 'numeric',
        message: 'Price must be numeric'
    ),
    new ValidationRule(
        field: 'price',
        type: 'min',
        message: 'Price must be at least 0.01',
        constraint: 0.01
    ),
    
    // Stock validation
    new ValidationRule(
        field: 'stock_quantity',
        type: 'integer',
        message: 'Stock quantity must be an integer'
    ),
    new ValidationRule(
        field: 'stock_quantity',
        type: 'min',
        message: 'Stock quantity cannot be negative',
        constraint: 0
    ),
];

// ============================================================================
// Example 3: Import with TRANSACTIONAL Strategy (All-or-Nothing)
// ============================================================================

echo "=== Import with TRANSACTIONAL Strategy ===\n";

try {
    $result = $importManager->import(
        filePath: storage_path('imports/products.csv'),
        format: ImportFormat::CSV,
        handler: new ProductImportHandler(),
        mappings: $mappings,
        mode: ImportMode::CREATE,
        strategy: ImportStrategy::TRANSACTIONAL,
        transactionManager: $transactionManager,
        validationRules: $validationRules
    );
    
    // If we reach here, ALL rows were successfully imported
    echo "✅ All {$result->successCount} products imported successfully!\n";
    
} catch (\Throwable $e) {
    // If ANY row fails, ALL changes are rolled back
    echo "❌ Import failed: {$e->getMessage()}\n";
    echo "All changes have been rolled back.\n";
}

// ============================================================================
// Example 4: Import with BATCH Strategy (Partial Success)
// ============================================================================

echo "\n=== Import with BATCH Strategy ===\n";

$result = $importManager->import(
    filePath: storage_path('imports/products.csv'),
    format: ImportFormat::CSV,
    handler: new ProductImportHandler(),
    mappings: $mappings,
    mode: ImportMode::UPSERT,
    strategy: ImportStrategy::BATCH,
    transactionManager: $transactionManager,
    validationRules: $validationRules,
    batchSize: 100  // Process 100 rows at a time
);

echo "✅ Successfully imported: {$result->successCount} products\n";
echo "⏭️  Skipped: {$result->skippedCount} products\n";
echo "❌ Failed: {$result->failedCount} products\n";
echo "📊 Success Rate: {$result->getSuccessRate()}%\n";

// ============================================================================
// Example 5: Comprehensive Error Handling
// ============================================================================

if ($result->hasErrors()) {
    echo "\n=== Error Analysis ===\n";
    
    // Group by severity
    $errorCounts = $result->getErrorCountBySeverity();
    echo "\nBy Severity:\n";
    echo "  ⚠️  Warnings: {$errorCounts['WARNING']}\n";
    echo "  ❌ Errors: {$errorCounts['ERROR']}\n";
    echo "  🔴 Critical: {$errorCounts['CRITICAL']}\n";
    
    // Group by field
    echo "\nBy Field:\n";
    foreach ($result->getErrorsByField() as $field => $errors) {
        echo "  - {$field}: " . count($errors) . " errors\n";
        
        // Show first 3 errors for this field
        foreach (array_slice($errors, 0, 3) as $error) {
            echo "    Row {$error->rowNumber}: {$error->message}\n";
        }
        
        if (count($errors) > 3) {
            $remaining = count($errors) - 3;
            echo "    ... and {$remaining} more errors\n";
        }
    }
    
    // Group by row
    echo "\nBy Row:\n";
    foreach (array_slice($result->getErrorsByRow(), 0, 5) as $rowNumber => $errors) {
        echo "  Row {$rowNumber}: " . count($errors) . " error(s)\n";
        foreach ($errors as $error) {
            echo "    - {$error->field}: {$error->message}\n";
        }
    }
    
    // Export errors to CSV for user review
    $errorsCsv = "row_number,field,severity,message,original_value\n";
    foreach ($result->getAllErrors() as $error) {
        $errorsCsv .= "{$error->rowNumber},{$error->field},{$error->severity->value},{$error->message},{$error->originalValue}\n";
    }
    file_put_contents(storage_path('imports/errors.csv'), $errorsCsv);
    echo "\n📄 Errors exported to: imports/errors.csv\n";
}

// ============================================================================
// Example 6: Import with STREAM Strategy (Large Files)
// ============================================================================

echo "\n=== Import with STREAM Strategy (Large Files) ===\n";

$result = $importManager->import(
    filePath: storage_path('imports/large-products.csv'),
    format: ImportFormat::CSV,
    handler: new ProductImportHandler(),
    mappings: $mappings,
    mode: ImportMode::UPSERT,
    strategy: ImportStrategy::STREAM  // No transactions, minimal memory
    // Note: No transactionManager needed for STREAM
);

echo "✅ Processed {$result->getTotalProcessed()} rows\n";
echo "Success Rate: {$result->getSuccessRate()}%\n";

// ============================================================================
// Example 7: Dry Run (Validation Only)
// ============================================================================

echo "\n=== Dry Run (Validation Only) ===\n";

$result = $importManager->validate(
    filePath: storage_path('imports/products.csv'),
    format: ImportFormat::CSV,
    handler: new ProductImportHandler(),
    mappings: $mappings,
    validationRules: $validationRules
);

// No data persisted, only validation performed
if ($result->hasErrors()) {
    echo "⚠️  Found {$result->getErrorCount()} validation errors:\n";
    foreach ($result->getErrorsByField() as $field => $errors) {
        echo "  - {$field}: " . count($errors) . " errors\n";
    }
    echo "\nFix these errors before importing.\n";
} else {
    echo "✅ Validation passed! Ready to import {$result->getTotalProcessed()} rows.\n";
}

// ============================================================================
// Example 8: JSON Import
// ============================================================================

echo "\n=== JSON Import ===\n";

$result = $importManager->import(
    filePath: storage_path('imports/products.json'),
    format: ImportFormat::JSON,
    handler: new ProductImportHandler(),
    mappings: $mappings,
    mode: ImportMode::UPSERT,
    strategy: ImportStrategy::BATCH,
    transactionManager: $transactionManager
);

echo "✅ Imported {$result->successCount} products from JSON\n";

/**
 * Expected JSON format (products.json):
 *
 * [
 *   {
 *     "product_name": "Widget A",
 *     "sku": "WGT-001",
 *     "price": "19.99",
 *     "stock": "100",
 *     "is_active": "yes",
 *     "created_date": "2024-01-15"
 *   },
 *   {
 *     "product_name": "Widget B",
 *     "sku": "WGT-002",
 *     "price": "29.99",
 *     "stock": "50",
 *     "is_active": "true",
 *     "created_date": "2024-02-20"
 *   }
 * ]
 */

// ============================================================================
// Example 9: Custom Import Handler with Complex Logic
// ============================================================================

final class ProductImportHandler implements ImportHandlerInterface
{
    public function __construct(
        private readonly ProductRepository $repository
    ) {}

    public function handle(array $data, ImportMode $mode): void
    {
        match($mode) {
            ImportMode::CREATE => $this->repository->create($data),
            ImportMode::UPDATE => $this->repository->update($data),
            ImportMode::UPSERT => $this->repository->upsert($data),
            ImportMode::DELETE => $this->repository->delete($data),
            ImportMode::SYNC => throw new \LogicException('SYNC not supported for products')
        };
    }

    public function getUniqueKeyFields(): array
    {
        return ['sku'];
    }

    public function getRequiredFields(): array
    {
        return ['name', 'sku', 'price'];
    }

    public function supportsMode(ImportMode $mode): bool
    {
        return $mode !== ImportMode::SYNC;
    }

    public function exists(array $uniqueData): bool
    {
        return $this->repository->existsBySku($uniqueData['sku']);
    }

    public function validateData(array $data): array
    {
        $errors = [];
        
        // Custom business rule: Price must be reasonable
        if (isset($data['price']) && $data['price'] > 10000) {
            $errors[] = 'Price exceeds maximum allowed (10,000)';
        }
        
        // Custom business rule: Stock alert
        if (isset($data['stock_quantity']) && $data['stock_quantity'] < 10) {
            // This is a warning, not an error - import will proceed
            logger()->warning("Low stock for SKU {$data['sku']}: {$data['stock_quantity']}");
        }
        
        // Custom business rule: SKU format
        if (isset($data['sku']) && !preg_match('/^[A-Z]{3}-\d{3}$/', $data['sku'])) {
            $errors[] = 'SKU must be in format XXX-999 (e.g., WGT-001)';
        }
        
        return $errors;
    }
}
