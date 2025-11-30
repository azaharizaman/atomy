<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Nexus Reporting Package
 *
 * This example demonstrates advanced features:
 * 1. Scheduled report generation
 * 2. Retention tiers and lifecycle management
 * 3. Multi-channel distribution
 * 4. Report job handling and background processing
 * 5. Custom report generator implementation
 * 6. Audit trail integration
 *
 * @package Nexus\Reporting
 */

use Nexus\Reporting\Services\ReportManager;
use Nexus\Reporting\Services\ReportJobHandler;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Contracts\ReportGeneratorInterface;
use Nexus\Reporting\Contracts\ReportDistributorInterface;
use Nexus\Reporting\Contracts\ReportRetentionInterface;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportSchedule;
use Nexus\Reporting\ValueObjects\RetentionTier;
use Nexus\Reporting\ValueObjects\ReportResult;
use Nexus\Reporting\Enums\ScheduleType;
use Nexus\Reporting\Enums\DistributionChannel;
use Nexus\Reporting\Exceptions\ReportGenerationException;

// ============================================
// Example 1: Scheduled Report Generation
// ============================================

/**
 * Schedule a report to run automatically.
 * Reports can be scheduled for: once, daily, weekly, monthly, yearly, or cron.
 */
function scheduleReportExamples(ReportManager $reportManager, string $reportId): void
{
    echo "=== Scheduling Examples ===\n\n";

    // One-time scheduled report
    $oneTimeSchedule = ReportSchedule::once(
        runAt: new \DateTimeImmutable('2024-12-31 23:59:59')
    );
    echo "One-time schedule: Run at {$oneTimeSchedule->startsAt->format('Y-m-d H:i:s')}\n";

    // Daily report at 8 AM
    $dailySchedule = ReportSchedule::daily(
        startsAt: new \DateTimeImmutable('2024-01-01 08:00:00'),
        endsAt: new \DateTimeImmutable('2024-12-31 08:00:00')
    );
    echo "Daily schedule: Every day from {$dailySchedule->startsAt->format('Y-m-d')} to {$dailySchedule->endsAt->format('Y-m-d')}\n";

    // Weekly report on Mondays at 9 AM
    $weeklySchedule = ReportSchedule::weekly(
        startsAt: new \DateTimeImmutable('2024-01-01 09:00:00'),
        endsAt: null,  // Run indefinitely
        maxOccurrences: 52  // Stop after 52 weeks (1 year)
    );
    echo "Weekly schedule: Every week, max 52 occurrences\n";

    // Monthly report on the 1st at midnight
    $monthlySchedule = ReportSchedule::monthly(
        startsAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
        endsAt: new \DateTimeImmutable('2025-01-01 00:00:00')
    );
    echo "Monthly schedule: 1st of each month for one year\n";

    // Custom cron expression: Every Friday at 5 PM
    $cronSchedule = ReportSchedule::cron(
        expression: '0 17 * * 5',  // Friday 5 PM
        startsAt: new \DateTimeImmutable('2024-01-01'),
        endsAt: null
    );
    echo "Cron schedule: Every Friday at 5 PM\n";

    // Actually schedule the report with distribution
    $jobId = $reportManager->scheduleReport(
        reportId: $reportId,
        schedule: $weeklySchedule,
        recipients: [
            'management@company.com',
            'sales@company.com',
        ],
        channel: 'email'
    );

    echo "\nScheduled job created with ID: {$jobId}\n";
}

// ============================================
// Example 2: Retention Tiers Management
// ============================================

/**
 * Retention tiers control how long reports are kept
 * and their storage behavior.
 *
 * Tiers:
 * - active: Current reports, full access
 * - archive: Older reports, possibly compressed
 * - compliance: Regulatory retention, immutable
 * - expired: Ready for deletion
 */
function retentionTierExamples(ReportRetentionInterface $retentionManager): void
{
    echo "\n=== Retention Tier Examples ===\n\n";

    // List reports approaching expiration
    $expiringReports = $retentionManager->getExpiringReports(
        daysUntilExpiration: 30
    );
    echo "Reports expiring in next 30 days: " . count($expiringReports) . "\n";

    // Move old reports to archive tier
    $archivedCount = $retentionManager->archiveOldReports(
        olderThan: new \DateTimeImmutable('-90 days')
    );
    echo "Reports moved to archive: {$archivedCount}\n";

    // Clean up expired reports
    $deletedCount = $retentionManager->cleanupExpiredReports();
    echo "Expired reports cleaned up: {$deletedCount}\n";

    // Mark reports for compliance hold (prevents deletion)
    $reportId = '01HABCDEF123456789XYZ';
    $retentionManager->setComplianceHold(
        reportId: $reportId,
        reason: 'Legal hold - Case #12345',
        holdUntil: new \DateTimeImmutable('+7 years')
    );
    echo "Compliance hold set for report {$reportId}\n";
}

// ============================================
// Example 3: Multi-Channel Distribution
// ============================================

/**
 * Distribute reports through multiple channels.
 * Channels: email, slack, webhook, in_app
 */
function multiChannelDistribution(ReportManager $reportManager, string $reportId): void
{
    echo "\n=== Multi-Channel Distribution ===\n\n";

    // Generate the report first
    $result = $reportManager->generateReport($reportId);

    // Email distribution (with attachment)
    $emailDistribution = $reportManager->distributeReport(
        reportId: $result->reportId,
        recipients: ['team@company.com', 'manager@company.com'],
        channel: 'email'
    );
    echo "Email distribution: {$emailDistribution->successCount} successful\n";

    // Slack distribution (posts link to report)
    $slackDistribution = $reportManager->distributeReport(
        reportId: $result->reportId,
        recipients: ['#sales-reports', '#management'],
        channel: 'slack'
    );
    echo "Slack distribution: {$slackDistribution->successCount} channels\n";

    // Webhook distribution (POST to endpoints)
    $webhookDistribution = $reportManager->distributeReport(
        reportId: $result->reportId,
        recipients: [
            'https://analytics.company.com/reports/ingest',
            'https://dashboard.company.com/api/reports',
        ],
        channel: 'webhook'
    );
    echo "Webhook distribution: {$webhookDistribution->successCount} endpoints\n";

    // In-app notification (creates notification in Notifier)
    $inAppDistribution = $reportManager->distributeReport(
        reportId: $result->reportId,
        recipients: [
            'user:01HABC123',  // User ID format
            'user:01HDEF456',
        ],
        channel: 'in_app'
    );
    echo "In-app distribution: {$inAppDistribution->successCount} users notified\n";
}

// ============================================
// Example 4: Background Job Processing
// ============================================

/**
 * Process scheduled reports via background jobs.
 * This is typically called from a scheduled command.
 */
function processScheduledJobs(ReportJobHandler $jobHandler): void
{
    echo "\n=== Processing Scheduled Jobs ===\n\n";

    // Get all jobs due for execution
    $dueJobs = $jobHandler->getDueJobs();
    echo "Jobs due for execution: " . count($dueJobs) . "\n";

    // Process each job
    foreach ($dueJobs as $job) {
        try {
            echo "Processing job: {$job->id}...\n";

            // Execute the report generation
            $result = $jobHandler->executeJob($job);

            echo "  ✓ Generated: {$result->filePath}\n";
            echo "  ✓ Duration: {$result->durationMs}ms\n";

            // If job has distribution config, distribute
            if ($job->hasDistributionConfig()) {
                $jobHandler->distributeJob($job, $result);
                echo "  ✓ Distributed to " . count($job->recipients) . " recipients\n";
            }

            // Mark job as completed
            $jobHandler->completeJob($job);
            echo "  ✓ Job completed\n";

        } catch (ReportGenerationException $e) {
            echo "  ✗ Failed: {$e->getMessage()}\n";
            $jobHandler->failJob($job, $e->getMessage());
        }
    }
}

// ============================================
// Example 5: Custom Report Generator
// ============================================

/**
 * Implement a custom report generator for specialized
 * output formats or data sources.
 */
final readonly class CustomPdfReportGenerator implements ReportGeneratorInterface
{
    public function __construct(
        private AnalyticsQueryInterface $analytics,
        private ExportManagerInterface $export,
        private StorageInterface $storage
    ) {}

    public function generate(
        ReportDefinitionInterface $definition,
        array $parameterOverrides = []
    ): ReportResult {
        $startTime = microtime(true);

        // Merge parameters
        $parameters = array_merge(
            $definition->getParameters(),
            $parameterOverrides
        );

        // Execute the analytics query
        $queryResult = $this->analytics->executeQuery(
            queryId: $definition->getQueryId(),
            parameters: $parameters
        );

        // Generate report using Export package
        $content = $this->export->export(
            data: $queryResult->getData(),
            format: $definition->getFormat(),
            templateId: $definition->getTemplateId(),
            options: [
                'title' => $definition->getName(),
                'generated_at' => new \DateTimeImmutable(),
                'parameters' => $parameters,
            ]
        );

        // Store the file
        $filePath = $this->generateFilePath($definition);
        $this->storage->put($filePath, $content);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        return new ReportResult(
            reportId: $this->generateUlid(),
            definitionId: $definition->getId(),
            filePath: $filePath,
            fileSize: strlen($content),
            durationMs: $durationMs,
            format: $definition->getFormat(),
            retentionTier: $definition->getRetentionTier(),
            queryResultId: $queryResult->getId(),
            generatedAt: new \DateTimeImmutable()
        );
    }

    public function supports(ReportFormat $format): bool
    {
        return $format === ReportFormat::PDF;
    }

    private function generateFilePath(ReportDefinitionInterface $definition): string
    {
        $date = new \DateTimeImmutable();
        return sprintf(
            'reports/%s/%s/%s.pdf',
            $date->format('Y/m'),
            $definition->getTenantId(),
            $this->generateUlid()
        );
    }

    private function generateUlid(): string
    {
        // In real implementation, use symfony/uid or similar
        return strtoupper(bin2hex(random_bytes(13)));
    }
}

// ============================================
// Example 6: Integration with Audit Trail
// ============================================

/**
 * Report operations are automatically logged to the
 * AuditLogger package for compliance tracking.
 */
function auditIntegrationExample(ReportManager $reportManager, string $reportId): void
{
    echo "\n=== Audit Trail Integration ===\n\n";

    // All ReportManager operations are automatically audited:
    //
    // 1. Report Definition Created
    //    Action: report_definition_created
    //    Entity: report_definitions
    //    Details: name, format, query_id, created_by
    //
    // 2. Report Generated
    //    Action: report_generated
    //    Entity: generated_reports
    //    Details: definition_id, file_path, duration_ms, format
    //
    // 3. Report Distributed
    //    Action: report_distributed
    //    Entity: report_distributions
    //    Details: report_id, channel, recipient_count, success_count
    //
    // 4. Report Archived
    //    Action: report_archived
    //    Entity: report_definitions
    //    Details: reason, archived_by
    //
    // 5. Retention Tier Changed
    //    Action: retention_tier_changed
    //    Entity: generated_reports
    //    Details: old_tier, new_tier, reason

    // Example: Query audit trail for a report
    // (This would be done via AuditLogger package)
    echo "Audit events for report {$reportId}:\n";
    echo "  - report_definition_created (2024-01-15 10:30:00)\n";
    echo "  - report_generated (2024-01-15 10:31:23)\n";
    echo "  - report_distributed (2024-01-15 10:31:45)\n";
    echo "  - retention_tier_changed (2024-04-15 00:00:00)\n";
}

// ============================================
// Example 7: Error Handling Patterns
// ============================================

/**
 * Comprehensive error handling for report operations.
 *
 * Note: The use statements below would normally be at the top of
 * the file. They are shown inline here for documentation purposes.
 *
 * use Nexus\Reporting\Exceptions\ReportNotFoundException;
 * use Nexus\Reporting\Exceptions\ReportGenerationException;
 * use Nexus\Reporting\Exceptions\ReportDistributionException;
 * use Nexus\Reporting\Exceptions\UnauthorizedReportException;
 * use Nexus\Reporting\Exceptions\InvalidScheduleException;
 * use Nexus\Reporting\Exceptions\RetentionPolicyException;
 */
function errorHandlingPatterns(ReportManager $reportManager, string $reportId): void
{
    echo "\n=== Error Handling Patterns ===\n\n";

    try {
        $result = $reportManager->generateReport($reportId);

    } catch (ReportNotFoundException $e) {
        // Report definition not found
        echo "Report not found: {$e->getReportId()}\n";
        // Log and return 404

    } catch (UnauthorizedReportException $e) {
        // User lacks permission to access this report
        echo "Unauthorized: {$e->getMessage()}\n";
        // Log security event and return 403

    } catch (ReportGenerationException $e) {
        // Generation failed (query error, export error, storage error)
        echo "Generation failed: {$e->getMessage()}\n";

        // Get the underlying cause
        $cause = $e->getPrevious();
        if ($cause !== null) {
            echo "Cause: {$cause->getMessage()}\n";
        }

        // Log for investigation and return 500

    } catch (InvalidScheduleException $e) {
        // Invalid schedule configuration
        echo "Invalid schedule: {$e->getMessage()}\n";
        // Validation error, return 422

    } catch (RetentionPolicyException $e) {
        // Cannot delete (compliance hold, etc.)
        echo "Retention policy violation: {$e->getMessage()}\n";
        // Return 409 Conflict
    }
}

// ============================================
// Complete Advanced Example
// ============================================

function completeAdvancedExample(
    ReportManager $reportManager,
    ReportJobHandler $jobHandler,
    ReportRetentionInterface $retentionManager
): void {
    echo "=== Nexus Reporting Advanced Usage ===\n\n";

    // Create a scheduled report
    $definition = $reportManager->createReport(
        name: 'Weekly Executive Summary',
        queryId: '01HABC123456789ABCDEF',
        format: ReportFormat::PDF,
        parameters: [
            'date_range' => 'last_7_days',
            'include_charts' => true,
            'detail_level' => 'executive',
        ]
    );

    // Schedule weekly execution with email distribution
    scheduleReportExamples($reportManager, $definition->getId());

    // Show retention management
    retentionTierExamples($retentionManager);

    // Demonstrate multi-channel distribution
    multiChannelDistribution($reportManager, $definition->getId());

    // Process any due jobs
    processScheduledJobs($jobHandler);

    // Show audit integration
    auditIntegrationExample($reportManager, $definition->getId());

    // Error handling patterns
    errorHandlingPatterns($reportManager, $definition->getId());

    echo "\n=== Complete ===\n";
}

// ============================================
// How to Run This Example
// ============================================

/*
 * Laravel:
 * ```php
 * $reportManager = app(ReportManager::class);
 * $jobHandler = app(ReportJobHandler::class);
 * $retentionManager = app(ReportRetentionInterface::class);
 *
 * completeAdvancedExample($reportManager, $jobHandler, $retentionManager);
 * ```
 *
 * Symfony:
 * ```php
 * $reportManager = $container->get(ReportManager::class);
 * $jobHandler = $container->get(ReportJobHandler::class);
 * $retentionManager = $container->get(ReportRetentionInterface::class);
 *
 * completeAdvancedExample($reportManager, $jobHandler, $retentionManager);
 * ```
 *
 * For scheduled job processing, create a console command:
 *
 * Laravel (app/Console/Commands/ProcessScheduledReports.php):
 * ```php
 * protected $signature = 'reports:process-scheduled';
 *
 * public function handle(ReportJobHandler $handler): void
 * {
 *     $handler->processScheduledJobs();
 * }
 * ```
 *
 * Then in Kernel.php:
 * ```php
 * $schedule->command('reports:process-scheduled')->everyMinute();
 * ```
 */
