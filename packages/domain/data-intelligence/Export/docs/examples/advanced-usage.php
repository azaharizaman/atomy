<?php

/**
 * Advanced Export Usage Examples
 * 
 * This file demonstrates advanced export scenarios:
 * - Template rendering with variables, conditionals, loops
 * - Custom formatters
 * - Multi-format batch exports
 * - Integration with other Nexus packages
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Nexus\Export\Services\ExportManager;
use Nexus\Export\Core\Formatters\CsvFormatter;
use Nexus\Export\Core\Formatters\JsonFormatter;
use Nexus\Export\Core\Engine\DefinitionValidator;
use Nexus\Export\Core\Engine\TemplateRenderer;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportMetadata;
use Nexus\Export\ValueObjects\ExportSection;
use Nexus\Export\ValueObjects\TableStructure;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportDestination;
use Psr\Log\NullLogger;

// ===================================================================
// Example 1: Template Rendering with Variables and Conditionals
// ===================================================================

echo "Example 1: Template Rendering\n";
echo str_repeat("=", 60) . "\n";

// Create export manager with template engine
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [],  // No formatters needed for template demo
    templateEngine: new TemplateRenderer(),
    logger: new NullLogger()
);

// Define template with variables, conditionals, and loops
$template = <<<'TEMPLATE'
INVOICE: {{metadata.title}}
Generated: {{metadata.generatedAt|date:Y-m-d H:i:s}}
Author: {{metadata.author}}

==========================================
CUSTOMER INFORMATION
==========================================
Name: {{customer.name}}
@if(customer.vip)
⭐ VIP CUSTOMER - Priority Support
@else
Standard Customer
@endif

==========================================
LINE ITEMS
==========================================
@foreach(lineItems as item)
{{item.description}}
  Quantity: {{item.quantity}}
  Unit Price: ${{item.unit_price|number:2}}
  Total: ${{item.total|number:2}}

@endforeach
==========================================
SUMMARY
==========================================
Subtotal: ${{summary.subtotal|number:2}}
Tax (6%): ${{summary.tax|number:2}}
Total: ${{summary.total|number:2}}

@if(summary.total > 5000)
🎉 BULK ORDER DISCOUNT APPLIED!
@endif
TEMPLATE;

// Template data
$templateData = [
    'metadata' => [
        'title' => 'INV-2024-001',
        'generatedAt' => new \DateTimeImmutable(),
        'author' => 'Billing System'
    ],
    'customer' => [
        'name' => 'Acme Corporation',
        'vip' => true
    ],
    'lineItems' => [
        [
            'description' => 'Professional Services - Web Development',
            'quantity' => 40,
            'unit_price' => 150.00,
            'total' => 6000.00
        ],
        [
            'description' => 'Consulting - System Architecture',
            'quantity' => 20,
            'unit_price' => 200.00,
            'total' => 4000.00
        ]
    ],
    'summary' => [
        'subtotal' => 10000.00,
        'tax' => 600.00,
        'total' => 10600.00
    ]
];

// Render template
$templateEngine = new TemplateRenderer();
$rendered = $templateEngine->render($template, $templateData);

echo "✅ Template rendered successfully!\n\n";
echo $rendered;
echo "\n\n";


// ===================================================================
// Example 2: Multi-Format Batch Export
// ===================================================================

echo "Example 2: Multi-Format Batch Export\n";
echo str_repeat("=", 60) . "\n";

// Create export definition
$reportDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Quarterly Sales Report Q4 2024',
        author: 'Sales Manager',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection(
            title: 'Executive Summary',
            content: [
                'total_sales' => 1250000.00,
                'total_orders' => 4500,
                'average_order_value' => 277.78,
                'growth_rate' => '15.5%'
            ]
        ),
        new ExportSection(
            title: 'Top Products',
            content: new TableStructure(
                headers: ['Product', 'Units Sold', 'Revenue', 'Market Share'],
                rows: [
                    ['Widget A', 1200, 300000.00, '24%'],
                    ['Widget B', 900, 450000.00, '36%'],
                    ['Widget C', 600, 300000.00, '24%'],
                    ['Widget D', 450, 200000.00, '16%']
                ]
            )
        )
    ]
);

// Export to multiple formats
$formats = [
    ExportFormat::CSV,
    ExportFormat::JSON,
];

$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::CSV => new CsvFormatter(),
        ExportFormat::JSON => new JsonFormatter(),
    ],
    templateEngine: null,
    logger: new NullLogger()
);

foreach ($formats as $format) {
    $result = $exportManager->export(
        definition: $reportDefinition,
        format: $format,
        destination: ExportDestination::DOWNLOAD
    );
    
    echo "✅ {$format->value} Export completed\n";
    echo "   File: {$result->getFilePath()}\n";
    echo "   Duration: {$result->getDuration()}ms\n";
    echo "   Size: " . number_format(filesize($result->getFilePath()) / 1024, 2) . " KB\n";
    echo "\n";
    
    // Cleanup
    unlink($result->getFilePath());
}


// ===================================================================
// Example 3: Nested Sections (Hierarchical Data)
// ===================================================================

echo "Example 3: Nested Sections (Hierarchical Data)\n";
echo str_repeat("=", 60) . "\n";

// Create hierarchical report structure
$hierarchicalReportDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Company Organizational Report',
        author: 'HR Department',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection(
            title: 'Engineering Department',
            content: ['headcount' => 45, 'budget' => 4500000],
            level: 0,
            children: [
                new ExportSection(
                    title: 'Backend Team',
                    content: ['headcount' => 20, 'projects' => 8],
                    level: 1,
                    children: [
                        new ExportSection(
                            title: 'API Development',
                            content: ['developers' => 10, 'projects' => 4],
                            level: 2
                        ),
                        new ExportSection(
                            title: 'Database Engineering',
                            content: ['developers' => 10, 'projects' => 4],
                            level: 2
                        )
                    ]
                ),
                new ExportSection(
                    title: 'Frontend Team',
                    content: ['headcount' => 15, 'projects' => 6],
                    level: 1
                ),
                new ExportSection(
                    title: 'DevOps Team',
                    content: ['headcount' => 10, 'projects' => 3],
                    level: 1
                )
            ]
        ),
        new ExportSection(
            title: 'Sales Department',
            content: ['headcount' => 30, 'budget' => 3000000],
            level: 0,
            children: [
                new ExportSection(
                    title: 'Inside Sales',
                    content: ['headcount' => 15, 'revenue_target' => 5000000],
                    level: 1
                ),
                new ExportSection(
                    title: 'Field Sales',
                    content: ['headcount' => 15, 'revenue_target' => 10000000],
                    level: 1
                )
            ]
        )
    ]
);

// Export to JSON
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::JSON => new JsonFormatter(),
    ],
    templateEngine: null,
    logger: new NullLogger()
);

$result = $exportManager->export(
    definition: $hierarchicalReportDefinition,
    format: ExportFormat::JSON,
    destination: ExportDestination::DOWNLOAD
);

echo "✅ Hierarchical Export completed successfully!\n";
echo "   File: {$result->getFilePath()}\n";
echo "   Depth: 3 levels (Department → Team → Sub-Team)\n";
echo "   Content:\n";
echo json_encode(json_decode(file_get_contents($result->getFilePath())), JSON_PRETTY_PRINT);
echo "\n\n";

// Cleanup
unlink($result->getFilePath());


// ===================================================================
// Example 4: Export with Format Hints
// ===================================================================

echo "Example 4: Export with Format Hints\n";
echo str_repeat("=", 60) . "\n";

// Create export definition with format-specific hints
$reportWithHintsDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Financial Statement',
        author: 'CFO',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0',
        watermark: 'CONFIDENTIAL',
        security: [
            'classification' => 'restricted',
            'retention_days' => 2555  // 7 years
        ]
    ),
    structure: [
        new ExportSection(
            title: 'Balance Sheet',
            content: new TableStructure(
                headers: ['Account', 'Debit', 'Credit'],
                rows: [
                    ['Cash', 100000.00, 0],
                    ['Accounts Receivable', 50000.00, 0],
                    ['Accounts Payable', 0, 30000.00],
                    ['Equity', 0, 120000.00]
                ]
            )
        )
    ],
    formatHints: [
        'pdf' => [
            'orientation' => 'landscape',
            'page_size' => 'A4',
            'watermark' => true
        ],
        'excel' => [
            'sheet_name' => 'Balance Sheet',
            'freeze_header' => true,
            'auto_filter' => true
        ],
        'csv' => [
            'delimiter' => ';',
            'enclosure' => '"',
            'encoding' => 'UTF-8'
        ]
    ]
);

// Export to CSV with custom delimiter
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::CSV => new CsvFormatter(),
    ],
    templateEngine: null,
    logger: new NullLogger()
);

$result = $exportManager->export(
    definition: $reportWithHintsDefinition,
    format: ExportFormat::CSV,
    destination: ExportDestination::DOWNLOAD
);

echo "✅ Export with Format Hints completed!\n";
echo "   File: {$result->getFilePath()}\n";
echo "   Watermark: {$reportWithHintsDefinition->getMetadata()->watermark}\n";
echo "   Security Classification: {$reportWithHintsDefinition->getMetadata()->security['classification']}\n";
echo "   Format Hints: " . json_encode($reportWithHintsDefinition->getFormatHints()['csv']) . "\n";
echo "\n";

// Cleanup
unlink($result->getFilePath());


// ===================================================================
// Example 5: ExportDefinition Serialization (Queuing)
// ===================================================================

echo "Example 5: ExportDefinition Serialization (for Queuing)\n";
echo str_repeat("=", 60) . "\n";

// Create export definition
$queuedExportDefinition = new ExportDefinition(
    metadata: new ExportMetadata(
        title: 'Annual Report 2024',
        author: 'System',
        generatedAt: new \DateTimeImmutable(),
        schemaVersion: '1.0'
    ),
    structure: [
        new ExportSection(
            title: 'Summary',
            content: ['year' => 2024, 'revenue' => 10000000]
        )
    ]
);

// Serialize to JSON (for queuing or API transmission)
$serialized = $queuedExportDefinition->toJson();
echo "✅ Serialized ExportDefinition:\n";
echo $serialized;
echo "\n\n";

// Deserialize back to ExportDefinition
$deserialized = ExportDefinition::fromJson($serialized);
echo "✅ Deserialized successfully!\n";
echo "   Title: {$deserialized->getMetadata()->title}\n";
echo "   Schema Version: {$deserialized->getMetadata()->schemaVersion}\n";
echo "   Structure Count: " . count($deserialized->getStructure()) . "\n";
echo "\n";


// ===================================================================
// Example 6: Error Handling
// ===================================================================

echo "Example 6: Error Handling\n";
echo str_repeat("=", 60) . "\n";

// Attempt invalid export (unsupported format)
try {
    $exportManager = new ExportManager(
        validator: new DefinitionValidator(),
        formatters: [
            ExportFormat::CSV => new CsvFormatter(),
        ],
        templateEngine: null,
        logger: new NullLogger()
    );
    
    $testDefinition = new ExportDefinition(
        metadata: new ExportMetadata(
            title: 'Test Report',
            author: 'System',
            generatedAt: new \DateTimeImmutable(),
            schemaVersion: '1.0'
        ),
        structure: []
    );
    
    // Try to export to PDF (no formatter registered)
    $result = $exportManager->export(
        definition: $testDefinition,
        format: ExportFormat::PDF,  // Not registered!
        destination: ExportDestination::DOWNLOAD
    );
    
} catch (\Nexus\Export\Exceptions\UnsupportedFormatException $e) {
    echo "❌ Expected error caught:\n";
    echo "   {$e->getMessage()}\n";
    echo "✅ Error handling works correctly!\n";
}

echo "\n";

// Attempt export with invalid table structure
try {
    $invalidTableDefinition = new ExportDefinition(
        metadata: new ExportMetadata(
            title: 'Invalid Table Test',
            author: 'System',
            generatedAt: new \DateTimeImmutable(),
            schemaVersion: '1.0'
        ),
        structure: [
            new ExportSection(
                title: 'Bad Table',
                content: new TableStructure(
                    headers: ['Col1', 'Col2', 'Col3'],
                    rows: [
                        ['Value1', 'Value2'],  // Missing column!
                    ]
                )
            )
        ]
    );
    
} catch (\Exception $e) {
    echo "❌ Expected validation error caught:\n";
    echo "   {$e->getMessage()}\n";
    echo "✅ Validation works correctly!\n";
}

echo "\n";

echo str_repeat("=", 60) . "\n";
echo "All advanced examples completed successfully!\n";
echo str_repeat("=", 60) . "\n";
