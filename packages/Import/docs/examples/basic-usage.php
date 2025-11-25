<?php

declare(strict_types=1);

/**
 * Basic Import Example: Customer CSV Import
 *
 * This example demonstrates the simplest way to import customer data from a CSV file.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Import\Services\ImportManager;
use Nexus\Import\ValueObjects\{ImportFormat, ImportMode, ImportStrategy, FieldMapping};
use Nexus\Import\Contracts\{ImportHandlerInterface, TransactionManagerInterface};

// Assume Laravel/Symfony container available
$importManager = app(ImportManager::class);
$transactionManager = app(TransactionManagerInterface::class);

// Define field mappings
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

// Execute import
$result = $importManager->import(
    filePath: storage_path('imports/customers.csv'),
    format: ImportFormat::CSV,
    handler: new YourCustomerImportHandler(),
    mappings: $mappings,
    mode: ImportMode::UPSERT,
    strategy: ImportStrategy::BATCH,
    transactionManager: $transactionManager
);

// Display results
echo "Import Results:\n";
echo "---------------\n";
echo "✅ Successfully imported: {$result->successCount} customers\n";
echo "⏭️  Skipped: {$result->skippedCount} rows\n";
echo "❌ Failed: {$result->failedCount} rows\n";
echo "📊 Success Rate: {$result->getSuccessRate()}%\n";

// Display errors if any
if ($result->hasErrors()) {
    echo "\nErrors by Field:\n";
    foreach ($result->getErrorsByField() as $field => $errors) {
        echo "  - {$field}: " . count($errors) . " errors\n";
        foreach (array_slice($errors, 0, 3) as $error) {
            echo "    Row {$error->rowNumber}: {$error->message}\n";
        }
    }
}

/**
 * Expected CSV format (customers.csv):
 *
 * customer_name,customer_email,phone
 * John Doe,john@example.com,555-1234
 * Jane Smith,jane@example.com,555-5678
 * Bob Johnson,bob@example.com,
 *
 * After transformation:
 * - customer_name → name (capitalized)
 * - customer_email → email (lowercase)
 * - phone → phone_number (default: "N/A" if empty)
 */
