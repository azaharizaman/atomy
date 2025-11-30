<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Nexus Reporting Package
 *
 * This example demonstrates:
 * 1. Creating a report definition
 * 2. Generating a report
 * 3. Distributing a report via email
 * 4. Handling errors gracefully
 *
 * @package Nexus\Reporting
 */

use Nexus\Reporting\Services\ReportManager;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\RetentionTier;
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use Nexus\Reporting\Exceptions\ReportGenerationException;

// ============================================
// Example 1: Create a Report Definition
// ============================================

/**
 * Creating a report definition stores the configuration
 * for generating reports. The definition references an
 * Analytics query that provides the data.
 */
function createReportDefinition(ReportManager $reportManager): string
{
    // Create a monthly sales report definition
    $definition = $reportManager->createReport(
        name: 'Monthly Sales Summary',
        queryId: '01HABC123456789ABCDEF',  // Your Analytics query ID
        format: ReportFormat::PDF,
        parameters: [
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
            'region' => 'APAC',
            'include_charts' => true,
        ],
        templateId: '01HDEF789012345GHIJK',  // Optional custom template
    );

    echo "Created report definition: {$definition->getId()}\n";
    echo "Name: {$definition->getName()}\n";
    echo "Format: {$definition->getFormat()->value}\n";

    return $definition->getId();
}

// ============================================
// Example 2: Generate a Report
// ============================================

/**
 * Generating a report:
 * 1. Fetches the definition
 * 2. Executes the Analytics query
 * 3. Renders the output via Export package
 * 4. Stores the file via Storage package
 */
function generateReport(ReportManager $reportManager, string $reportId): void
{
    try {
        // Generate with default parameters from definition
        $result = $reportManager->generateReport($reportId);

        echo "Report generated successfully!\n";
        echo "Report ID: {$result->reportId}\n";
        echo "File Path: {$result->filePath}\n";
        echo "File Size: " . number_format($result->fileSize / 1024, 2) . " KB\n";
        echo "Duration: {$result->durationMs}ms\n";
        echo "Format: {$result->format->value}\n";
        echo "Retention Tier: {$result->retentionTier->value}\n";

    } catch (ReportNotFoundException $e) {
        echo "Error: Report not found - {$e->getMessage()}\n";
    } catch (ReportGenerationException $e) {
        echo "Error: Generation failed - {$e->getMessage()}\n";
    }
}

// ============================================
// Example 3: Generate with Parameter Overrides
// ============================================

/**
 * You can override default parameters at generation time.
 * Useful for running the same report with different filters.
 */
function generateWithOverrides(ReportManager $reportManager, string $reportId): void
{
    try {
        // Override the date range for this specific generation
        $result = $reportManager->generateReport(
            reportId: $reportId,
            parameterOverrides: [
                'date_from' => '2024-02-01',
                'date_to' => '2024-02-29',
                'region' => 'EMEA',  // Override region too
            ]
        );

        echo "Generated report with overrides: {$result->reportId}\n";

    } catch (ReportGenerationException $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}

// ============================================
// Example 4: Distribute a Report
// ============================================

/**
 * Distributing sends the generated report to recipients
 * via the Notifier package (email, Slack, webhook, etc.)
 */
function distributeReport(ReportManager $reportManager, string $generatedReportId): void
{
    try {
        $distribution = $reportManager->distributeReport(
            reportId: $generatedReportId,
            recipients: [
                'sales-team@company.com',
                'manager@company.com',
                'cfo@company.com',
            ],
            channel: 'email'
        );

        echo "Distribution complete!\n";
        echo "Successful: {$distribution->successCount} recipients\n";
        echo "Failed: {$distribution->failureCount} recipients\n";

        if ($distribution->failureCount > 0) {
            echo "Failed recipients:\n";
            foreach ($distribution->errors as $recipient => $error) {
                echo "  - {$recipient}: {$error}\n";
            }
        }

    } catch (\Exception $e) {
        echo "Distribution error: {$e->getMessage()}\n";
    }
}

// ============================================
// Example 5: Archive a Report Definition
// ============================================

/**
 * Archiving a report definition marks it as inactive
 * but retains it for historical reference.
 */
function archiveReport(ReportManager $reportManager, string $reportId): void
{
    try {
        $reportManager->archiveReport($reportId);
        echo "Report definition archived successfully\n";

    } catch (ReportNotFoundException $e) {
        echo "Error: Report not found - {$e->getMessage()}\n";
    }
}

// ============================================
// Example 6: Generate Multiple Formats
// ============================================

/**
 * Generate the same report in multiple formats.
 * Useful for providing download options to users.
 */
function generateMultipleFormats(ReportManager $reportManager, string $queryId): void
{
    $formats = [
        ReportFormat::PDF,
        ReportFormat::EXCEL,
        ReportFormat::CSV,
    ];

    echo "Generating report in multiple formats...\n";

    foreach ($formats as $format) {
        // Create temporary definition for each format
        $definition = $reportManager->createReport(
            name: "Sales Report - {$format->value}",
            queryId: $queryId,
            format: $format,
            parameters: [
                'date_from' => '2024-01-01',
                'date_to' => '2024-01-31',
            ]
        );

        $result = $reportManager->generateReport($definition->getId());

        echo sprintf(
            "  %s: %s (%.2f KB)\n",
            $format->value,
            $result->filePath,
            $result->fileSize / 1024
        );
    }
}

// ============================================
// Complete Usage Example
// ============================================

/**
 * Complete workflow demonstrating typical usage.
 *
 * In a real application, ReportManager would be injected
 * via dependency injection, not instantiated directly.
 */
function completeExample(ReportManager $reportManager): void
{
    echo "=== Nexus Reporting Basic Usage ===\n\n";

    // Step 1: Create a report definition
    echo "1. Creating report definition...\n";
    $reportId = createReportDefinition($reportManager);
    echo "\n";

    // Step 2: Generate the report
    echo "2. Generating report...\n";
    generateReport($reportManager, $reportId);
    echo "\n";

    // Step 3: Generate with different parameters
    echo "3. Generating with overrides...\n";
    generateWithOverrides($reportManager, $reportId);
    echo "\n";

    // Step 4: Distribute the report (simulated)
    // In real usage, you'd use the generated report ID
    echo "4. Distribution would happen here...\n";
    echo "\n";

    // Step 5: Archive when no longer needed
    echo "5. Archiving report definition...\n";
    archiveReport($reportManager, $reportId);
    echo "\n";

    echo "=== Complete ===\n";
}

// ============================================
// How to Run This Example
// ============================================

/*
 * This example assumes you have the ReportManager properly
 * configured with all required dependencies. In a real
 * Laravel or Symfony application:
 *
 * Laravel:
 * ```php
 * $reportManager = app(ReportManager::class);
 * completeExample($reportManager);
 * ```
 *
 * Symfony:
 * ```php
 * $reportManager = $container->get(ReportManager::class);
 * completeExample($reportManager);
 * ```
 *
 * Expected Output:
 * ```
 * === Nexus Reporting Basic Usage ===
 *
 * 1. Creating report definition...
 * Created report definition: 01HABC...
 * Name: Monthly Sales Summary
 * Format: pdf
 *
 * 2. Generating report...
 * Report generated successfully!
 * Report ID: 01HDEF...
 * File Path: reports/2024/01/31/01HDEF....pdf
 * File Size: 245.67 KB
 * Duration: 1234ms
 * Format: pdf
 * Retention Tier: active
 *
 * ... and so on
 * ```
 */
