<?php

declare(strict_types=1);

namespace Nexus\Reporting\Core\Engine;

use Nexus\Analytics\Contracts\AnalyticsAuthorizerInterface;
use Nexus\Analytics\Contracts\AnalyticsManagerInterface;
use Nexus\Analytics\Contracts\QueryResultInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Export\Contracts\ExportDestination;
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportMetadata;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\Contracts\ReportGeneratorInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Contracts\ReportTemplateInterface;
use Nexus\Reporting\Exceptions\ReportGenerationException;
use Nexus\Reporting\Exceptions\UnauthorizedReportException;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportResult;
use Nexus\Reporting\ValueObjects\RetentionTier;
use Nexus\Scheduler\Contracts\ScheduleManagerInterface;
use Nexus\Scheduler\ValueObjects\JobType;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Psr\Log\LoggerInterface;

/**
 * Core engine for generating reports from Analytics queries.
 *
 * Flow: Analytics Query → Export Definition → Rendered File → Storage
 */
final readonly class ReportGenerator implements ReportGeneratorInterface
{
    public function __construct(
        private AnalyticsManagerInterface $analyticsManager,
        private AnalyticsAuthorizerInterface $analyticsAuthorizer,
        private ExportManagerInterface $exportManager,
        private ReportRepositoryInterface $reportRepository,
        private ScheduleManagerInterface $scheduleManager,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger,
        private ?ReportTemplateInterface $defaultTemplate = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function generate(
        ReportDefinitionInterface $definition,
        array $parameters = []
    ): ReportResult {
        $startTime = hrtime(true);
        $reportId = $this->generateReportId();

        try {
            // SEC-REP-0401: Check permission inheritance from Analytics
            $this->checkPermission($definition->getQueryId());

            $this->logger->info('Starting report generation', [
                'report_id' => $reportId,
                'query_id' => $definition->getQueryId(),
                'format' => $definition->getFormat()->value,
            ]);

            // Step 1: Execute Analytics query
            $queryResult = $this->executeQuery(
                $definition->getQueryId(),
                array_merge($definition->getParameters(), $parameters)
            );

            // Step 2: Transform to Export Definition
            $exportDefinition = $this->buildExportDefinition(
                $definition,
                $queryResult
            );

            // Step 3: Render via Export Manager
            $exportResult = $this->exportManager->export(
                $exportDefinition,
                $this->mapFormatToExportFormat($definition->getFormat()),
                ExportDestination::STORAGE
            );

            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            // Create successful result
            $result = ReportResult::success(
                reportId: $reportId,
                format: $definition->getFormat(),
                filePath: $exportResult->getFilePath(),
                fileSize: $exportResult->getFileSize(),
                generatedAt: new \DateTimeImmutable(),
                durationMs: $durationMs,
                queryResultId: $queryResult->getQueryId()
            );

            // Store in repository
            $this->reportRepository->storeGeneratedReport([
                ...$result->toArray(),
                'report_definition_id' => $definition->getId(),
                'generated_by' => $this->getCurrentUserId(),
                'tenant_id' => $definition->getTenantId(),
            ]);

            // Audit log
            $this->auditLogger->log(
                logName: 'report_generated',
                description: "Report '{$definition->getName()}' generated successfully",
                subjectType: 'Report',
                subjectId: $reportId,
                level: 3, // High
                properties: [
                    'query_id' => $definition->getQueryId(),
                    'format' => $definition->getFormat()->value,
                    'file_size' => $result->getFormattedSize(),
                    'duration' => $result->getFormattedDuration(),
                ]
            );

            $this->logger->info('Report generation completed', [
                'report_id' => $reportId,
                'duration_ms' => $durationMs,
                'file_size' => $result->getFileSize(),
            ]);

            return $result;

        } catch (UnauthorizedReportException $e) {
            throw $e; // Re-throw authorization errors
        } catch (\Throwable $e) {
            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            $this->logger->error('Report generation failed', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            // Store failure result
            $result = ReportResult::failure(
                reportId: $reportId,
                format: $definition->getFormat(),
                generatedAt: new \DateTimeImmutable(),
                durationMs: $durationMs,
                error: $e->getMessage()
            );

            $this->reportRepository->storeGeneratedReport([
                ...$result->toArray(),
                'report_definition_id' => $definition->getId(),
                'generated_by' => $this->getCurrentUserId(),
                'tenant_id' => $definition->getTenantId(),
            ]);

            throw ReportGenerationException::withContext(
                'Report generation failed',
                ['report_id' => $reportId, 'query_id' => $definition->getQueryId()],
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateFromQuery(
        string $queryId,
        ReportFormat $format,
        array $parameters = []
    ): ReportResult {
        $startTime = hrtime(true);
        $reportId = $this->generateReportId();

        try {
            // Check permissions
            $this->checkPermission($queryId);

            // Execute query
            $queryResult = $this->executeQuery($queryId, $parameters);

            // Build minimal export definition
            $exportDefinition = new ExportDefinition(
                metadata: new ExportMetadata(
                    title: "Ad-hoc Report - Query {$queryId}",
                    generatedAt: new \DateTimeImmutable(),
                    schemaVersion: '1.0'
                ),
                structure: $this->buildStructureFromQueryResult($queryResult),
                formatHints: []
            );

            // Export
            $exportResult = $this->exportManager->export(
                $exportDefinition,
                $this->mapFormatToExportFormat($format),
                ExportDestination::STORAGE
            );

            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            $result = ReportResult::success(
                reportId: $reportId,
                format: $format,
                filePath: $exportResult->getFilePath(),
                fileSize: $exportResult->getFileSize(),
                generatedAt: new \DateTimeImmutable(),
                durationMs: $durationMs,
                queryResultId: $queryResult->getQueryId()
            );

            // Store without definition reference
            $this->reportRepository->storeGeneratedReport([
                ...$result->toArray(),
                'report_definition_id' => null, // Ad-hoc
                'generated_by' => $this->getCurrentUserId(),
                'tenant_id' => null,
            ]);

            return $result;

        } catch (\Throwable $e) {
            throw ReportGenerationException::queryExecutionFailed($queryId, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function previewReport(
        ReportDefinitionInterface $definition,
        array $parameters = []
    ): QueryResultInterface {
        // SEC-REP-0401: Check permissions
        $this->checkPermission($definition->getQueryId());

        $this->logger->info('Generating report preview (no storage)', [
            'query_id' => $definition->getQueryId(),
        ]);

        // Execute query but don't store anything
        return $this->executeQuery(
            $definition->getQueryId(),
            array_merge($definition->getParameters(), $parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function generateBatch(
        ReportDefinitionInterface $definition,
        array $entityIds
    ): array {
        $this->logger->info('Scheduling batch report generation', [
            'report_id' => $definition->getId(),
            'entity_count' => count($entityIds),
        ]);

        $jobIds = [];

        foreach ($entityIds as $entityId) {
            // Create scheduled job for each entity
            $job = $this->scheduleManager->schedule(
                new ScheduleDefinition(
                    jobType: JobType::EXPORT_REPORT,
                    targetId: $definition->getId(),
                    runAt: new \DateTimeImmutable(),
                    payload: [
                        'entity_id' => $entityId,
                        'is_batch' => true,
                    ],
                    priority: 5, // Medium priority
                )
            );

            $jobIds[] = $job->getId();
        }

        $this->logger->info('Batch jobs scheduled', [
            'job_count' => count($jobIds),
        ]);

        return $jobIds;
    }

    /**
     * Check if current user has permission to execute the query.
     *
     * @throws UnauthorizedReportException
     */
    private function checkPermission(string $queryId): void
    {
        $userId = $this->getCurrentUserId();

        if (!$this->analyticsAuthorizer->can($userId, 'execute', $queryId)) {
            throw UnauthorizedReportException::cannotExecuteQuery($userId, $queryId);
        }
    }

    /**
     * Execute an Analytics query.
     *
     * @param array<string, mixed> $parameters
     */
    private function executeQuery(string $queryId, array $parameters): QueryResultInterface
    {
        try {
            // Note: runQuery signature may vary based on actual Analytics implementation
            // Adjust this call based on your Analytics package API
            return $this->analyticsManager->runQuery($queryId, '', '', $parameters);
        } catch (\Throwable $e) {
            throw ReportGenerationException::queryExecutionFailed($queryId, $e);
        }
    }

    /**
     * Build Export Definition from report definition and query result.
     */
    private function buildExportDefinition(
        ReportDefinitionInterface $definition,
        QueryResultInterface $queryResult
    ): ExportDefinition {
        $metadata = new ExportMetadata(
            title: $definition->getName(),
            description: $definition->getDescription(),
            generatedAt: new \DateTimeImmutable(),
            schemaVersion: '1.0'
        );

        $structure = $this->buildStructureFromQueryResult($queryResult);

        // Merge template configuration if available
        $formatHints = [];
        if ($templateConfig = $definition->getTemplateConfig()) {
            $formatHints = $templateConfig;
        }

        return new ExportDefinition(
            metadata: $metadata,
            structure: $structure,
            formatHints: $formatHints
        );
    }

    /**
     * Transform query result data to export structure.
     *
     * @return array<string, mixed>
     */
    private function buildStructureFromQueryResult(QueryResultInterface $queryResult): array
    {
        return [
            'data' => $queryResult->getData(),
            'metadata' => $queryResult->getMetadata(),
        ];
    }

    /**
     * Map ReportFormat to Export package format enum.
     */
    private function mapFormatToExportFormat(ReportFormat $format): \Nexus\Export\ValueObjects\ExportFormat
    {
        return match ($format) {
            ReportFormat::PDF => \Nexus\Export\ValueObjects\ExportFormat::PDF,
            ReportFormat::EXCEL => \Nexus\Export\ValueObjects\ExportFormat::EXCEL,
            ReportFormat::CSV => \Nexus\Export\ValueObjects\ExportFormat::CSV,
            ReportFormat::JSON => \Nexus\Export\ValueObjects\ExportFormat::JSON,
            ReportFormat::HTML => \Nexus\Export\ValueObjects\ExportFormat::HTML,
        };
    }

    /**
     * Generate a unique report ID (ULID).
     */
    private function generateReportId(): string
    {
        return (string) \Illuminate\Support\Str::ulid();
    }

    /**
     * Get current user ID from context.
     *
     * TODO: Inject proper AuthContextInterface
     */
    private function getCurrentUserId(): string
    {
        // Placeholder - should be injected via AuthContextInterface
        return 'system';
    }
}
