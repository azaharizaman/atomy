<?php

declare(strict_types=1);

namespace Nexus\Assets\Integration;

use Nexus\Assets\Services\DepreciationScheduler;
use Nexus\Scheduler\Contracts\JobHandlerInterface;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduledJob;
use Psr\Log\LoggerInterface;

/**
 * Depreciation Job Handler - Integration with Nexus\Scheduler.
 *
 * Enables automated monthly depreciation runs via the scheduler.
 *
 * Job Configuration Example (in apps/Atomy):
 * ```php
 * $scheduleManager->createJob([
 *     'name' => 'Monthly Asset Depreciation',
 *     'job_type' => JobType::ASSET_DEPRECIATION,
 *     'recurrence_rule' => RecurrenceRule::monthly(1), // 1st of each month
 *     'scheduled_time' => '00:00:00',
 *     'payload' => [
 *         'category_ids' => ['CAT-001'], // Optional: limit to specific categories
 *         'location_ids' => ['LOC-HQ'],   // Optional: limit to specific locations
 *     ],
 *     'is_active' => true,
 * ]);
 * ```
 *
 * Workflow:
 * 1. Scheduler triggers job on schedule (e.g., monthly)
 * 2. This handler extracts period dates and options from payload
 * 3. Calls DepreciationScheduler to process depreciation
 * 4. Returns JobResult with success/failure and metrics
 */
final readonly class DepreciationJobHandler implements JobHandlerInterface
{
    public function __construct(
        private DepreciationScheduler $scheduler,
        private LoggerInterface $logger
    ) {}

    public function supports(JobType $jobType): bool
    {
        // Note: This requires adding ASSET_DEPRECIATION to JobType enum in Atomy
        return $jobType->value === 'asset_depreciation';
    }

    public function handle(ScheduledJob $job): JobResult
    {
        try {
            $payload = $job->getPayload();

            // Extract period dates from payload or calculate from current month
            $periodStart = $this->extractPeriodStart($payload);
            $periodEnd = $this->extractPeriodEnd($payload);

            // Extract processing options
            $options = [
                'asset_ids' => $payload['asset_ids'] ?? null,
                'category_ids' => $payload['category_ids'] ?? null,
                'location_ids' => $payload['location_ids'] ?? null,
                'units_consumed' => $payload['units_consumed'] ?? [],
            ];

            // Remove null values
            $options = array_filter($options, fn($value) => $value !== null);

            $this->logger->info('Starting scheduled depreciation job', [
                'job_id' => $job->getId(),
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'options' => $options,
            ]);

            // Process depreciation
            $result = $this->scheduler->processDepreciation(
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                options: $options
            );

            // Determine if job succeeded
            $hasFailures = !empty($result['failures']);
            $isPartialSuccess = $result['processed'] > 0 && $hasFailures;

            if ($hasFailures && $result['processed'] === 0) {
                // Total failure
                return JobResult::failure(
                    message: 'All assets failed to depreciate',
                    output: $result,
                    shouldRetry: true,
                    retryDelaySeconds: 3600 // Retry in 1 hour
                );
            }

            if ($isPartialSuccess) {
                // Partial success - log warning but mark as success
                $this->logger->warning('Depreciation job completed with some failures', [
                    'processed' => $result['processed'],
                    'failures' => count($result['failures']),
                ]);
            }

            return JobResult::success(
                message: sprintf(
                    'Depreciation completed: %d assets, $%.2f total',
                    $result['processed'],
                    $result['total_amount']
                ),
                output: $result
            );
        } catch (\Throwable $e) {
            $this->logger->error('Depreciation job failed', [
                'job_id' => $job->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return JobResult::failure(
                message: 'Depreciation job failed: ' . $e->getMessage(),
                output: ['exception' => $e->getMessage()],
                shouldRetry: true,
                retryDelaySeconds: 3600
            );
        }
    }

    /**
     * Extract period start date from payload or use current month's start.
     */
    private function extractPeriodStart(array $payload): \DateTimeImmutable
    {
        if (isset($payload['period_start'])) {
            return new \DateTimeImmutable($payload['period_start']);
        }

        // Default: First day of current month
        return new \DateTimeImmutable('first day of this month 00:00:00');
    }

    /**
     * Extract period end date from payload or use current month's end.
     */
    private function extractPeriodEnd(array $payload): \DateTimeImmutable
    {
        if (isset($payload['period_end'])) {
            return new \DateTimeImmutable($payload['period_end']);
        }

        // Default: Last day of current month
        return new \DateTimeImmutable('last day of this month 23:59:59');
    }
}
