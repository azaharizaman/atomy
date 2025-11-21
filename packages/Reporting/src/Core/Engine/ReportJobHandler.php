<?php

declare(strict_types=1);

namespace Nexus\Reporting\Core\Engine;

use Nexus\Reporting\Contracts\ReportDistributorInterface;
use Nexus\Reporting\Contracts\ReportGeneratorInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use Nexus\Scheduler\Contracts\JobHandlerInterface;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\JobStatus;
use Nexus\Scheduler\ValueObjects\JobType;
use Nexus\Scheduler\ValueObjects\ScheduledJob;
use Psr\Log\LoggerInterface;

/**
 * Handles scheduled report generation jobs from Nexus\Scheduler.
 *
 * Implements PER-REP-0301: Offloads jobs >5 seconds to queue workers.
 */
final readonly class ReportJobHandler implements JobHandlerInterface
{
    public function __construct(
        private ReportGeneratorInterface $reportGenerator,
        private ReportDistributorInterface $reportDistributor,
        private ReportRepositoryInterface $reportRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function supports(JobType $jobType): bool
    {
        return $jobType === JobType::EXPORT_REPORT;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ScheduledJob $job): JobResult
    {
        $this->logger->info('Handling report generation job', [
            'job_id' => $job->id,
            'report_id' => $job->targetId,
        ]);

        try {
            // Get report definition
            $reportDefinition = $this->reportRepository->findById($job->targetId);
            if (!$reportDefinition) {
                return JobResult::failure(
                    error: "Report definition not found: {$job->targetId}",
                    shouldRetry: false // Don't retry if definition doesn't exist
                );
            }

            // Check if this is a batch job
            $isBatch = $job->payload['is_batch'] ?? false;
            $parameters = [];

            if ($isBatch) {
                // Extract entity ID from batch payload
                $entityId = $job->payload['entity_id'] ?? null;
                if (!$entityId) {
                    return JobResult::failure(
                        error: 'Batch job missing entity_id in payload',
                        shouldRetry: false
                    );
                }

                $this->logger->info('Processing batch report', [
                    'job_id' => $job->id,
                    'entity_id' => $entityId,
                ]);

                // Add entity ID to parameters
                $parameters['entity_id'] = $entityId;
            }

            // Generate report
            $result = $this->reportGenerator->generate(
                $reportDefinition,
                $parameters
            );

            if (!$result->isSuccessful()) {
                // Generation failed
                return JobResult::failure(
                    error: $result->error ?? 'Unknown generation error',
                    shouldRetry: $this->shouldRetryError($result->error)
                );
            }

            // Auto-distribute to recipients if configured
            if (!empty($reportDefinition->getRecipients())) {
                try {
                    $distributionResult = $this->reportDistributor->distribute(
                        $result,
                        $reportDefinition->getRecipients()
                    );

                    // Partial distribution success is still considered success
                    // Failed recipients can be retried manually
                    if ($distributionResult->hasAnySuccess()) {
                        $this->logger->info('Report distributed', [
                            'job_id' => $job->id,
                            'report_id' => $result->reportId,
                            'success_count' => $distributionResult->successCount,
                            'failure_count' => $distributionResult->failureCount,
                        ]);
                    }

                } catch (\Throwable $e) {
                    // REL-REP-0305: Distribution failure doesn't fail the job
                    // PDF is preserved for manual retry
                    $this->logger->error('Distribution failed but report preserved', [
                        'job_id' => $job->id,
                        'report_id' => $result->reportId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Job succeeded
            return JobResult::success(
                data: [
                    'report_id' => $result->reportId,
                    'file_path' => $result->filePath,
                    'file_size' => $result->fileSize,
                    'duration_ms' => $result->durationMs,
                ]
            );

        } catch (\Nexus\Reporting\Exceptions\UnauthorizedReportException $e) {
            // Don't retry authorization failures
            $this->logger->error('Unauthorized report generation attempt', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            return JobResult::failure(
                error: $e->getMessage(),
                shouldRetry: false
            );

        } catch (\Nexus\Analytics\Exceptions\QueryExecutionException $e) {
            // Retry transient query failures
            $this->logger->warning('Query execution failed, will retry', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            return JobResult::failure(
                error: $e->getMessage(),
                shouldRetry: true
            );

        } catch (\Nexus\Export\Exceptions\ExportException $e) {
            // Retry transient export failures
            $this->logger->warning('Export failed, will retry', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            return JobResult::failure(
                error: $e->getMessage(),
                shouldRetry: true
            );

        } catch (\Throwable $e) {
            // Unknown error - retry with exponential backoff
            $this->logger->error('Unexpected error in report job', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return JobResult::failure(
                error: $e->getMessage(),
                shouldRetry: true
            );
        }
    }

    /**
     * Determine if an error should trigger a retry.
     */
    private function shouldRetryError(?string $error): bool
    {
        if ($error === null) {
            return false;
        }

        // Don't retry authorization or validation errors
        $nonRetryablePatterns = [
            'not authorized',
            'permission denied',
            'not found',
            'invalid',
            'malformed',
        ];

        $errorLower = strtolower($error);
        foreach ($nonRetryablePatterns as $pattern) {
            if (str_contains($errorLower, $pattern)) {
                return false;
            }
        }

        // Retry transient errors
        $retryablePatterns = [
            'timeout',
            'connection',
            'network',
            'temporary',
            'transient',
        ];

        foreach ($retryablePatterns as $pattern) {
            if (str_contains($errorLower, $pattern)) {
                return true;
            }
        }

        // Default: retry unknown errors
        return true;
    }
}
