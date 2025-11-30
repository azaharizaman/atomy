<?php

/**
 * Basic Export Usage Examples
 * 
 * This file demonstrates simple export scenarios using Nexus\Export.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Nexus\Export\Services\ExportManager;
use Nexus\Export\Core\Formatters\CsvFormatter;
use Nexus\Export\Core\Formatters\JsonFormatter;
use Nexus\Export\Core\Formatters\XmlFormatter;
use Nexus\Export\Core\Engine\DefinitionValidator;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportMetadata;
use Nexus\Export\ValueObjects\ExportSection;
use Nexus\Export\ValueObjects\TableStructure;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportDestination;
use Psr\Log\NullLogger;

// ===================================================================
// Example 1: Simple CSV Export
// ===================================================================

echo "Example 1: Simple CSV Export\n";
echo str_repeat("=", 60) . "\n";

// Create export manager
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::CSV => new CsvFormatter(),
    ],
    templateEngine: null,
    logger: new NullLogger()
);

// Create simple export definition
$salesReportDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Monthly Sales Report',
        author: 'System',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection(
            title: 'Sales Data',
            content: new TableStructure(
                headers: ['Date', 'Product', 'Quantity', 'Revenue'],
                rows: [
                    ['2024-11-01', 'Widget A', 50, 1250.00],
                    ['2024-11-02', 'Widget B', 30, 1500.00],
                    ['2024-11-03', 'Widget A', 45, 1125.00],
                    ['2024-11-04', 'Widget C', 20, 2000.00],
                ],
                footers: ['Total', '', 145, 5875.00]
            )
        )
    ]
);

// Export to CSV
$result = $exportManager->export(
    definition: $salesReportDefinition,
    format: ExportFormat::CSV,
    destination: ExportDestination::DOWNLOAD
);

echo "✅ CSV Export completed successfully!\n";
echo "   File: {$result->getFilePath()}\n";
echo "   Duration: {$result->getDuration()}ms\n";
echo "   Content:\n";
echo file_get_contents($result->getFilePath());
echo "\n\n";

// Cleanup
unlink($result->getFilePath());


// ===================================================================
// Example 2: JSON Export with Nested Sections
// ===================================================================

echo "Example 2: JSON Export with Nested Sections\n";
echo str_repeat("=", 60) . "\n";

// Update export manager to include JSON formatter
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::JSON => new JsonFormatter(),
    ],
    templateEngine: null,
    logger: new NullLogger()
);

// Create export definition with nested sections
$financialReportDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Financial Summary Q4 2024',
        author: 'CFO',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0',
        description: 'Quarterly financial report'
    ),
    structure: [
        new ExportSection(
            title: 'Summary',
            content: [
                'total_revenue' => 500000,
                'total_expenses' => 300000,
                'net_profit' => 200000,
                'profit_margin' => '40%'
            ]
        ),
        new ExportSection(
            title: 'Revenue Breakdown',
            content: [
                'product_sales' => 350000,
                'service_revenue' => 100000,
                'other_income' => 50000
            ]
        )
    ]
);

// Export to JSON
$result = $exportManager->export(
    definition: $financialReportDefinition,
    format: ExportFormat::JSON,
    destination: ExportDestination::DOWNLOAD
);

echo "✅ JSON Export completed successfully!\n";
echo "   File: {$result->getFilePath()}\n";
echo "   Duration: {$result->getDuration()}ms\n";
echo "   Content:\n";
echo file_get_contents($result->getFilePath());
echo "\n\n";

// Cleanup
unlink($result->getFilePath());


// ===================================================================
// Example 3: XML Export with Table Data
// ===================================================================

echo "Example 3: XML Export with Table Data\n";
echo str_repeat("=", 60) . "\n";

// Update export manager to include XML formatter
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::XML => new XmlFormatter(),
    ],
    templateEngine: null,
    logger: new NullLogger()
);

// Create export definition
$inventoryReportDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Inventory Report',
        author: 'Warehouse Manager',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection(
            title: 'Current Stock',
            content: new TableStructure(
                headers: ['SKU', 'Product Name', 'Quantity', 'Unit Price', 'Total Value'],
                rows: [
                    ['WDG-001', 'Widget A', 100, 25.00, 2500.00],
                    ['WDG-002', 'Widget B', 50, 50.00, 2500.00],
                    ['WDG-003', 'Widget C', 25, 100.00, 2500.00],
                ]
            )
        )
    ]
);

// Export to XML
$result = $exportManager->export(
    definition: $inventoryReportDefinition,
    format: ExportFormat::XML,
    destination: ExportDestination::DOWNLOAD
);

echo "✅ XML Export completed successfully!\n";
echo "   File: {$result->getFilePath()}\n";
echo "   Duration: {$result->getDuration()}ms\n";
echo "   Content:\n";
echo file_get_contents($result->getFilePath());
echo "\n\n";

// Cleanup
unlink($result->getFilePath());


// ===================================================================
// Example 4: Invoice Export (Real-World Scenario)
// ===================================================================

echo "Example 4: Invoice Export (Real-World Scenario)\n";
echo str_repeat("=", 60) . "\n";

// Simulate invoice data
$invoiceData = [
    'number' => 'INV-2024-001',
    'date' => '2024-11-24',
    'due_date' => '2024-12-24',
    'customer' => [
        'name' => 'Acme Corporation',
        'address' => '123 Business St, City, State 12345',
        'email' => 'billing@acme.com'
    ],
    'line_items' => [
        ['description' => 'Professional Services', 'hours' => 40, 'rate' => 150.00, 'total' => 6000.00],
        ['description' => 'Consulting', 'hours' => 20, 'rate' => 200.00, 'total' => 4000.00],
    ],
    'subtotal' => 10000.00,
    'tax' => 600.00,
    'total' => 10600.00
];

// Create invoice export definition
$invoiceDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: "Invoice {$invoiceData['number']}",
        author: 'Accounts Receivable',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection(
            title: 'Invoice Header',
            content: [
                'invoice_number' => $invoiceData['number'],
                'invoice_date' => $invoiceData['date'],
                'due_date' => $invoiceData['due_date'],
            ]
        ),
        new ExportSection(
            title: 'Customer Information',
            content: [
                'name' => $invoiceData['customer']['name'],
                'address' => $invoiceData['customer']['address'],
                'email' => $invoiceData['customer']['email'],
            ]
        ),
        new ExportSection(
            title: 'Line Items',
            content: new TableStructure(
                headers: ['Description', 'Hours', 'Rate', 'Total'],
                rows: $invoiceData['line_items'],
                footers: []
            )
        ),
        new ExportSection(
            title: 'Totals',
            content: [
                'subtotal' => $invoiceData['subtotal'],
                'tax' => $invoiceData['tax'],
                'total' => $invoiceData['total']
            ]
        )
    ]
);

// Export to CSV
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::CSV => new CsvFormatter(),
    ],
    templateEngine: null,
    logger: new NullLogger()
);

$result = $exportManager->export(
    definition: $invoiceDefinition,
    format: ExportFormat::CSV,
    destination: ExportDestination::DOWNLOAD
);

echo "✅ Invoice Export completed successfully!\n";
echo "   File: {$result->getFilePath()}\n";
echo "   Duration: {$result->getDuration()}ms\n";
echo "   Content:\n";
echo file_get_contents($result->getFilePath());
echo "\n\n";

// Cleanup
unlink($result->getFilePath());


// ===================================================================
// Example 5: Large Dataset with Streaming (10K rows)
// ===================================================================

echo "Example 5: Large Dataset with Streaming (10K rows)\n";
echo str_repeat("=", 60) . "\n";

// Generate 10,000 rows of sample data
$largeDatasetRows = [];
for ($i = 1; $i <= 10000; $i++) {
    $largeDatasetRows[] = [
        date('Y-m-d', strtotime("-{$i} days")),
        "Product " . ($i % 100),
        rand(1, 100),
        round(rand(10, 500) / 10, 2),
    ];
}

// Create export definition
$largeDatasetDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Large Sales Dataset',
        author: 'System',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection(
            title: 'Sales Transactions',
            content: new TableStructure(
                headers: ['Date', 'Product', 'Quantity', 'Amount'],
                rows: $largeDatasetRows
            )
        )
    ]
);

// Export to CSV (streaming automatically enabled for large datasets)
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::CSV => new CsvFormatter(),
    ],
    templateEngine: null,
    logger: new NullLogger()
);

$startTime = microtime(true);
$result = $exportManager->export(
    definition: $largeDatasetDefinition,
    format: ExportFormat::CSV,
    destination: ExportDestination::DOWNLOAD
);
$executionTime = (microtime(true) - $startTime) * 1000;

$fileSize = filesize($result->getFilePath());

echo "✅ Large Dataset Export completed successfully!\n";
echo "   File: {$result->getFilePath()}\n";
echo "   File Size: " . number_format($fileSize / 1024, 2) . " KB\n";
echo "   Rows: 10,000\n";
echo "   Duration: " . round($executionTime, 2) . "ms\n";
echo "   Memory Used: " . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
echo "   ⚡ Streaming enabled for optimal performance\n";
echo "\n";

// Cleanup
unlink($result->getFilePath());

echo str_repeat("=", 60) . "\n";
echo "All examples completed successfully!\n";
echo str_repeat("=", 60) . "\n";
