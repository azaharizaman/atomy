<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Core\Engine;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Nexus\Scheduler\Contracts\ClockInterface;
use Nexus\Scheduler\Contracts\JobHandlerInterface;
use Nexus\Scheduler\Contracts\JobQueueInterface;
use Nexus\Scheduler\Contracts\ScheduleRepositoryInterface;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Exceptions\InvalidJobStateException;
use Nexus\Scheduler\Exceptions\NoHandlerFoundException;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Execution Engine
 *
 * Orchestrates job execution, status management, and retry logic.
 * Interprets JobResult from handlers and manages the execution mechanism.
 *
 * Responsibilities:
 * - Find and invoke appropriate handler
 * - Manage status transitions
 * - Handle retry logic (exponential backoff)
 * - Re-queue failed jobs with delays
 * - Schedule recurring job occurrences
 */
final readonly class ExecutionEngine
{
    private const DEFAULT_RETRY_DELAYS = [60, 120, 240, 480, 960]; // Exponential backoff in seconds
    
    public function __construct(
        private ScheduleRepositoryInterface $repository,
        private JobQueueInterface $queue,
        private ClockInterface $clock,
        private RecurrenceEngine $recurrenceEngine,
        private LoggerInterface $logger,
    ) {}
    
    /**
     * Execute a job with the given handler
     *
     * This method orchestrates the complete execution flow:
     * 1. Validate job can execute
     * 2. Transition to RUNNING
     * 3. Invoke handler
     * 4. Process result (success/failure/retry)
     * 5. Update job status
     * 6. Handle recurrence if needed
     *
     * @param ScheduledJob $job Job to execute
     * @param JobHandlerInterface $handler Handler for this job type
     * @return JobResult Execution result
     */
    public function execute(ScheduledJob $job, JobHandlerInterface $handler): JobResult
    {
        // Validate job can execute
        if (!$job->canExecute()) {
            throw new InvalidJobStateException(
                "Job {$job->id} cannot be executed in status: {$job->status->value}"
            );
        }
        
        // Transition to RUNNING
        $runningJob = $job->withStatus(JobStatus::RUNNING);
        $this->repository->save($runningJob);
        
        $this->logger->info('Executing scheduled job', [
            'jobId' => $job->id,
            'jobType' => $job->jobType->value,
            'targetId' => $job->targetId,
        ]);
        
        // Execute handler and measure duration
        $startTime = $this->clock->now();
        try {
            $result = $handler->handle($runningJob);
        } catch (\Throwable $e) {
            // Handler threw exception - treat as retriable failure
            $result = JobResult::failure(
                error: $e->getMessage(),
                shouldRetry: true
            );
            
            $this->logger->error('Job handler threw exception', [
                'jobId' => $job->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        $endTime = $this->clock->now();
        $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
        
        // Add timing to result
        $result = $result->withTiming($endTime, (float)$duration);
        
        // Process result and update job
        $updatedJob = $this->processResult($runningJob, $result);
        $this->repository->save($updatedJob);
        
        // Handle recurrence if job completed successfully
        if ($result->success && $job->isRecurring()) {
            $this->handleRecurrence($job);
        }
        
        return $result;
    }
    
    /**
     * Process execution result and determine next job state
     */
    private function processResult(ScheduledJob $job, JobResult $result): ScheduledJob
    {
        $jobWithResult = $job->withResult($result);
        
        if ($result->success) {
            // Success - mark as completed
            $this->logger->info('Job completed successfully', [
                'jobId' => $job->id,
                'duration' => $result->durationSeconds,
            ]);
            
            return $jobWithResult->withStatus(JobStatus::COMPLETED);
        }
        
        // Failure - check retry logic
        if ($result->isPermanentFailure()) {
            // Permanent failure - don't retry
            $this->logger->error('Job failed permanently', [
                'jobId' => $job->id,
                'error' => $result->error,
            ]);
            
            return $jobWithResult->withStatus(JobStatus::FAILED_PERMANENT);
        }
        
        // Retriable failure
        if (!$job->canRetry()) {
            // Max retries exceeded
            $this->logger->error('Job failed - max retries exceeded', [
                'jobId' => $job->id,
                'retryCount' => $job->retryCount,
                'maxRetries' => $job->maxRetries,
                'error' => $result->error,
            ]);
            
            return $jobWithResult->withStatus(JobStatus::FAILED_PERMANENT);
        }
        
        // Schedule retry
        $retryDelay = $result->getRetryDelay() ?? $this->calculateBackoff($job->retryCount);
        $retryAt = $this->clock->now()->modify("+{$retryDelay} seconds");
        
        $this->logger->warning('Job failed - scheduling retry', [
            'jobId' => $job->id,
            'retryCount' => $job->retryCount + 1,
            'retryDelay' => $retryDelay,
            'retryAt' => $retryAt->format('c'),
            'error' => $result->error,
        ]);
        
        // Update job for retry
        $retryJob = $jobWithResult
            ->withIncrementedRetry()
            ->withRunAt($retryAt)
            ->withStatus(JobStatus::PENDING);
        
        // Re-queue with delay
        $this->queue->dispatch($retryJob, $retryDelay);
        
        return $retryJob;
    }
    
    /**
     * Handle recurring job - schedule next occurrence
     */
    private function handleRecurrence(ScheduledJob $job): void
    {
        if ($job->recurrence === null) {
            return;
        }
        
        $nextRunTime = $this->recurrenceEngine->calculateNextRunTime(
            currentRunAt: $job->runAt,
            recurrence: $job->recurrence,
            occurrenceCount: $job->occurrenceCount
        );
        
        if ($nextRunTime === null) {
            $this->logger->info('Recurring job ended - no more occurrences', [
                'jobId' => $job->id,
                'occurrenceCount' => $job->occurrenceCount,
            ]);
            return;
        }
        
        // Create next occurrence
        $nextJob = $job->forNextOccurrence($nextRunTime);
        $this->repository->save($nextJob);
        
        $this->logger->info('Scheduled next occurrence for recurring job', [
            'jobId' => $job->id,
            'occurrenceCount' => $nextJob->occurrenceCount,
            'nextRunAt' => $nextRunTime->format('c'),
        ]);
    }
    
    /**
     * Calculate exponential backoff delay
     *
     * @param int $retryCount Current retry attempt (0-indexed)
     * @return int Delay in seconds
     */
    private function calculateBackoff(int $retryCount): int
    {
        // Use predefined delays, or continue exponential growth
        if ($retryCount < count(self::DEFAULT_RETRY_DELAYS)) {
            return self::DEFAULT_RETRY_DELAYS[$retryCount];
        }
        
        // For retries beyond predefined list, use exponential formula
        // 2^(retryCount) * 60, capped at 1 hour
        return min(pow(2, $retryCount) * 60, 3600);
    }
    
    /**
     * Find appropriate handler for a job
     *
     * @param ScheduledJob $job Job to find handler for
     * @param iterable<JobHandlerInterface> $handlers Available handlers
     * @return JobHandlerInterface Matching handler
     * @throws NoHandlerFoundException If no handler supports this job type
     */
    public function findHandler(ScheduledJob $job, iterable $handlers): JobHandlerInterface
    {
        foreach ($handlers as $handler) {
            if ($handler->supports($job->jobType)) {
                return $handler;
            }
        }
        
        throw new NoHandlerFoundException($job->jobType);
    }
}
