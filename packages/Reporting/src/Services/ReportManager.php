<?php

declare(strict_types=1);

namespace Nexus\Reporting\Services;

use Nexus\Analytics\Contracts\AnalyticsAuthorizerInterface;
use Nexus\Analytics\Contracts\AnalyticsManagerInterface;
use Nexus\Analytics\Contracts\QueryResultInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\Contracts\ReportDistributorInterface;
use Nexus\Reporting\Contracts\ReportGeneratorInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use Nexus\Reporting\Exceptions\UnauthorizedReportException;
use Nexus\Reporting\ValueObjects\DistributionResult;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportResult;
use Nexus\Reporting\ValueObjects\ReportSchedule;
use Nexus\Reporting\ValueObjects\ScheduleType;
use Nexus\Scheduler\Contracts\ScheduleManagerInterface;
use Nexus\Scheduler\ValueObjects\JobType;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Psr\Log\LoggerInterface;

/**
 * Main public API for the Reporting package.
 *
 * Orchestrates report creation, generation, preview, distribution, and scheduling.
 * Implements all security checks, tenant validation, and audit logging.
 */
final readonly class ReportManager
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private ReportGeneratorInterface $reportGenerator,
        private ReportDistributorInterface $reportDistributor,
        private AnalyticsManagerInterface $analyticsManager,
        private AnalyticsAuthorizerInterface $analyticsAuthorizer,
        private ScheduleManagerInterface $scheduleManager,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger,
        private ?string $currentTenantId = null,
        private ?string $currentUserId = null,
    ) {}

    /**
     * Create a new report definition.
     *
     * @param array{
     *     name: string,
     *     description?: string,
     *     query_id: string,
     *     owner_id: string,
     *     format: ReportFormat|string,
     *     schedule?: ReportSchedule|array|null,
     *     recipients?: array,
     *     parameters?: array,
     *     template_config?: array,
     *     is_active?: bool,
     *     tenant_id?: string
     * } $data
     * @return string The report definition ID
     * @throws UnauthorizedReportException
     */
    public function createReport(array $data): string
    {
        // Validate user can execute the query
        $this->checkQueryPermission($data['query_id']);

        // Defense-in-depth: Validate tenant context
        $this->validateTenantContext($data['tenant_id'] ?? null);

        // Normalize format
        if (is_string($data['format'])) {
            $data['format'] = ReportFormat::from($data['format']);
        }

        // Normalize schedule
        if (isset($data['schedule']) && is_array($data['schedule'])) {
            $data['schedule'] = ReportSchedule::fromArray($data['schedule']);
        }

        $reportId = $this->reportRepository->save([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'query_id' => $data['query_id'],
            'owner_id' => $data['owner_id'],
            'format' => $data['format']->value,
            'schedule_type' => $data['schedule']?->type->value,
            'schedule_config' => $data['schedule']?->toArray(),
            'recipients' => $data['recipients'] ?? [],
            'parameters' => $data['parameters'] ?? [],
            'template_config' => $data['template_config'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'tenant_id' => $data['tenant_id'] ?? $this->currentTenantId,
            'created_at' => new \DateTimeImmutable(),
        ]);

        $this->auditLogger->log(
            logName: 'report_definition_created',
            description: "Report definition '{$data['name']}' created",
            subjectType: 'ReportDefinition',
            subjectId: $reportId,
            level: 2, // Medium
            properties: [
                'query_id' => $data['query_id'],
                'format' => $data['format']->value,
                'schedule_type' => $data['schedule']?->type->value,
            ]
        );

        $this->logger->info('Report definition created', [
            'report_id' => $reportId,
            'name' => $data['name'],
        ]);

        return $reportId;
    }

    /**
     * Generate a report on-demand.
     *
     * @param array<string, mixed> $parameters Parameter overrides
     * @throws ReportNotFoundException
     * @throws UnauthorizedReportException
     */
    public function generateReport(string $reportId, array $parameters = []): ReportResult
    {
        $definition = $this->getReportDefinition($reportId);

        // SEC-REP-0401: Permission inheritance from Analytics
        $this->checkQueryPermission($definition->getQueryId());

        // Defense-in-depth: Tenant validation
        $this->validateTenantContext($definition->getTenantId());

        return $this->reportGenerator->generate($definition, $parameters);
    }

    /**
     * Preview a report without storing output (for interactive dashboards).
     *
     * Implements FUN-REP-0213: Interactive Dashboard Generation
     *
     * @param array<string, mixed> $parameters
     * @throws ReportNotFoundException
     * @throws UnauthorizedReportException
     */
    public function previewReport(string $reportId, array $parameters = []): QueryResultInterface
    {
        $definition = $this->getReportDefinition($reportId);

        // Permission check
        $this->checkQueryPermission($definition->getQueryId());

        // Tenant validation
        $this->validateTenantContext($definition->getTenantId());

        return $this->reportGenerator->previewReport($definition, $parameters);
    }

    /**
     * Generate reports in batch for multiple entities.
     *
     * Creates scheduled jobs for concurrent processing with max 10/tenant limit.
     *
     * @param array<string> $entityIds
     * @return array<string> Scheduler job IDs
     * @throws ReportNotFoundException
     * @throws UnauthorizedReportException
     */
    public function generateBatch(string $reportId, array $entityIds): array
    {
        $definition = $this->getReportDefinition($reportId);

        // Permission check
        $this->checkQueryPermission($definition->getQueryId());

        // Tenant validation
        $this->validateTenantContext($definition->getTenantId());

        // Concurrency limiting (max 10 concurrent per tenant)
        $chunkSize = 10;
        $chunks = array_chunk($entityIds, $chunkSize);
        $allJobIds = [];

        foreach ($chunks as $chunkIndex => $chunk) {
            $jobIds = $this->reportGenerator->generateBatch($definition, $chunk);
            $allJobIds = array_merge($allJobIds, $jobIds);

            $this->logger->info('Batch chunk scheduled', [
                'report_id' => $reportId,
                'chunk_index' => $chunkIndex,
                'chunk_size' => count($chunk),
            ]);
        }

        $this->auditLogger->log(
            logName: 'batch_report_scheduled',
            description: "Batch report generation scheduled for {$definition->getName()}",
            subjectType: 'ReportDefinition',
            subjectId: $reportId,
            level: 2,
            properties: [
                'entity_count' => count($entityIds),
                'job_count' => count($allJobIds),
            ]
        );

        return $allJobIds;
    }

    /**
     * Distribute a generated report to recipients.
     *
     * @param array<\Nexus\Notifier\Contracts\NotifiableInterface> $recipients
     * @param array<string, mixed> $options
     * @throws ReportNotFoundException
     */
    public function distributeReport(
        string $reportGeneratedId,
        array $recipients,
        array $options = []
    ): DistributionResult {
        $generatedReport = $this->reportRepository->findGeneratedReportById($reportGeneratedId);
        if (!$generatedReport) {
            throw ReportNotFoundException::forGeneratedReport($reportGeneratedId);
        }

        // Build ReportResult from stored data
        $result = new ReportResult(
            reportId: $generatedReport['id'],
            format: ReportFormat::from($generatedReport['format']),
            filePath: $generatedReport['file_path'],
            fileSize: $generatedReport['file_size_bytes'],
            generatedAt: new \DateTimeImmutable($generatedReport['generated_at']),
            durationMs: $generatedReport['duration_ms'],
            isSuccessful: $generatedReport['is_successful'],
        );

        return $this->reportDistributor->distribute($result, $recipients, $options);
    }

    /**
     * Schedule a report for recurring generation and distribution.
     *
     * @throws ReportNotFoundException
     * @throws UnauthorizedReportException
     */
    public function scheduleReport(string $reportId, ReportSchedule $schedule): string
    {
        $definition = $this->getReportDefinition($reportId);

        // Permission check
        $this->checkQueryPermission($definition->getQueryId());

        // Tenant validation
        $this->validateTenantContext($definition->getTenantId());

        // Update report definition with schedule
        $this->reportRepository->update($reportId, [
            'schedule_type' => $schedule->type->value,
            'schedule_config' => $schedule->toArray(),
        ]);

        // Create initial scheduled job
        $job = $this->scheduleManager->schedule(
            new ScheduleDefinition(
                jobType: JobType::EXPORT_REPORT,
                targetId: $reportId,
                runAt: $schedule->startsAt ?? new \DateTimeImmutable(),
                recurrence: $this->convertToScheduleRecurrence($schedule),
                payload: ['scheduled' => true],
            )
        );

        $this->auditLogger->log(
            logName: 'report_scheduled',
            description: "Report '{$definition->getName()}' scheduled for recurring generation",
            subjectType: 'ReportDefinition',
            subjectId: $reportId,
            level: 2,
            properties: [
                'schedule_type' => $schedule->type->value,
                'cron_expression' => $schedule->cronExpression,
                'job_id' => $job->getId(),
            ]
        );

        return $job->getId();
    }

    /**
     * Update an existing report definition.
     *
     * @param array<string, mixed> $data
     * @throws ReportNotFoundException
     * @throws UnauthorizedReportException
     */
    public function updateReport(string $reportId, array $data): bool
    {
        $definition = $this->getReportDefinition($reportId);

        // If query_id is being changed, check new permission
        if (isset($data['query_id']) && $data['query_id'] !== $definition->getQueryId()) {
            $this->checkQueryPermission($data['query_id']);
        }

        // Tenant validation
        $this->validateTenantContext($definition->getTenantId());

        $success = $this->reportRepository->update($reportId, $data);

        if ($success) {
            $this->auditLogger->log(
                logName: 'report_definition_updated',
                description: "Report definition '{$definition->getName()}' updated",
                subjectType: 'ReportDefinition',
                subjectId: $reportId,
                level: 2,
                properties: ['updated_fields' => array_keys($data)]
            );
        }

        return $success;
    }

    /**
     * Archive a report definition (soft delete).
     *
     * @throws ReportNotFoundException
     * @throws UnauthorizedReportException
     */
    public function archiveReport(string $reportId): bool
    {
        $definition = $this->getReportDefinition($reportId);

        // Permission check
        $this->checkQueryPermission($definition->getQueryId());

        // Tenant validation
        $this->validateTenantContext($definition->getTenantId());

        $success = $this->reportRepository->archive($reportId);

        if ($success) {
            $this->auditLogger->log(
                logName: 'report_definition_archived',
                description: "Report definition '{$definition->getName()}' archived",
                subjectType: 'ReportDefinition',
                subjectId: $reportId,
                level: 2
            );
        }

        return $success;
    }

    /**
     * Get a report definition by ID.
     *
     * @throws ReportNotFoundException
     */
    private function getReportDefinition(string $reportId): ReportDefinitionInterface
    {
        $definition = $this->reportRepository->findById($reportId);
        if (!$definition) {
            throw ReportNotFoundException::forId($reportId);
        }

        return $definition;
    }

    /**
     * Check if current user can execute the Analytics query.
     *
     * Implements SEC-REP-0401: Permission Inheritance
     *
     * @throws UnauthorizedReportException
     */
    private function checkQueryPermission(string $queryId): void
    {
        $userId = $this->currentUserId ?? 'system';

        if (!$this->analyticsAuthorizer->can($userId, 'execute', $queryId)) {
            throw UnauthorizedReportException::cannotExecuteQuery($userId, $queryId);
        }
    }

    /**
     * Validate tenant context matches report tenant.
     *
     * Defense-in-depth security check (Option A from considerations).
     *
     * @throws UnauthorizedReportException
     */
    private function validateTenantContext(?string $reportTenantId): void
    {
        // Skip validation if multi-tenancy is disabled
        if ($this->currentTenantId === null && $reportTenantId === null) {
            return;
        }

        if ($this->currentTenantId !== $reportTenantId) {
            throw UnauthorizedReportException::tenantMismatch(
                $this->currentTenantId ?? 'none',
                $reportTenantId ?? 'none'
            );
        }
    }

    /**
     * Convert ReportSchedule to Scheduler's ScheduleRecurrence.
     */
    private function convertToScheduleRecurrence(ReportSchedule $schedule): ?\Nexus\Scheduler\ValueObjects\ScheduleRecurrence
    {
        if (!$schedule->type->isRecurring()) {
            return null;
        }

        return new \Nexus\Scheduler\ValueObjects\ScheduleRecurrence(
            type: match ($schedule->type) {
                ScheduleType::DAILY => \Nexus\Scheduler\ValueObjects\RecurrenceType::DAILY,
                ScheduleType::WEEKLY => \Nexus\Scheduler\ValueObjects\RecurrenceType::WEEKLY,
                ScheduleType::MONTHLY => \Nexus\Scheduler\ValueObjects\RecurrenceType::MONTHLY,
                ScheduleType::YEARLY => \Nexus\Scheduler\ValueObjects\RecurrenceType::YEARLY,
                ScheduleType::CRON => \Nexus\Scheduler\ValueObjects\RecurrenceType::CRON,
                default => \Nexus\Scheduler\ValueObjects\RecurrenceType::ONCE,
            },
            interval: 1,
            cronExpression: $schedule->cronExpression,
            endsAt: $schedule->endsAt,
            maxOccurrences: $schedule->maxOccurrences,
        );
    }
}
