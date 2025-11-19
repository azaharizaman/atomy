<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Services;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Nexus\Scheduler\Contracts\ClockInterface;
use Nexus\Scheduler\Contracts\JobHandlerInterface;
use Nexus\Scheduler\Contracts\JobQueueInterface;
use Nexus\Scheduler\Contracts\ScheduleManagerInterface;
use Nexus\Scheduler\Contracts\ScheduleRepositoryInterface;
use Nexus\Scheduler\Core\Engine\ExecutionEngine;
use Nexus\Scheduler\Core\Engine\RecurrenceEngine;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Exceptions\InvalidJobStateException;
use Nexus\Scheduler\Exceptions\JobNotFoundException;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Schedule Manager
 *
 * Main orchestrator for scheduled job management.
 * Coordinates between repository, queue, handlers, and engines.
 */
final readonly class ScheduleManager implements ScheduleManagerInterface
{
    private ExecutionEngine $executionEngine;
    private RecurrenceEngine $recurrenceEngine;
    
    /**
     * @param ScheduleRepositoryInterface $repository Job persistence
     * @param JobQueueInterface $queue Job dispatching
     * @param ClockInterface $clock Time abstraction
     * @param iterable<JobHandlerInterface> $handlers Tagged handlers from service provider
     * @param LoggerInterface $logger PSR-3 logger
     */
    public function __construct(
        private ScheduleRepositoryInterface $repository,
        private JobQueueInterface $queue,
        private ClockInterface $clock,
        private iterable $handlers,
        private LoggerInterface $logger,
    ) {
        $this->recurrenceEngine = new RecurrenceEngine($clock);
        $this->executionEngine = new ExecutionEngine(
            repository: $repository,
            queue: $queue,
            clock: $clock,
            recurrenceEngine: $this->recurrenceEngine,
            logger: $logger,
        );
    }
    
    /**
     * Schedule a new job
     */
    public function schedule(ScheduleDefinition $definition): ScheduledJob
    {
        // Generate ULID for job
        $jobId = $this->generateUlid();
        
        // Create scheduled job from definition
        $job = ScheduledJob::fromDefinition($jobId, $definition);
        
        // Persist
        $this->repository->save($job);
        
        $this->logger->info('Scheduled new job', [
            'jobId' => $job->id,
            'jobType' => $job->jobType->value,
            'targetId' => $job->targetId,
            'runAt' => $job->runAt->format('c'),
            'isRecurring' => $job->isRecurring(),
        ]);
        
        // Queue for execution if it's already due
        if ($job->isDue($this->clock)) {
            $this->queue->dispatch($job);
        }
        
        return $job;
    }
    
    /**
     * Execute a scheduled job by ID
     */
    public function executeJob(string $jobId): JobResult
    {
        $job = $this->repository->find($jobId);
        
        if ($job === null) {
            throw new JobNotFoundException($jobId);
        }
        
        // Find handler for this job type
        $handler = $this->executionEngine->findHandler($job, $this->handlers);
        
        // Execute via engine
        return $this->executionEngine->execute($job, $handler);
    }
    
    /**
     * Cancel a scheduled job
     */
    public function cancelJob(string $jobId): bool
    {
        $job = $this->repository->find($jobId);
        
        if ($job === null) {
            return false;
        }
        
        // Cannot cancel jobs in final states
        if ($job->status->isFinal()) {
            $this->logger->warning('Cannot cancel job in final state', [
                'jobId' => $jobId,
                'status' => $job->status->value,
            ]);
            return false;
        }
        
        // Transition to CANCELED
        try {
            $canceledJob = $job->withStatus(JobStatus::CANCELED);
            $this->repository->save($canceledJob);
            
            $this->logger->info('Canceled scheduled job', [
                'jobId' => $jobId,
                'previousStatus' => $job->status->value,
            ]);
            
            return true;
        } catch (\LogicException $e) {
            $this->logger->error('Invalid state transition when canceling job', [
                'jobId' => $jobId,
                'status' => $job->status->value,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Get all jobs that are due for execution
     */
    public function getDueJobs(): array
    {
        return $this->repository->findDue($this->clock->now());
    }
    
    /**
     * Reschedule a job with new execution time
     */
    public function rescheduleJob(string $jobId, DateTimeImmutable $newRunAt): ScheduledJob
    {
        $job = $this->repository->find($jobId);
        
        if ($job === null) {
            throw new JobNotFoundException($jobId);
        }
        
        // Only pending jobs can be rescheduled
        if (!$job->status->canExecute()) {
            throw new InvalidJobStateException(
                "Cannot reschedule job {$jobId} in status: {$job->status->value}"
            );
        }
        
        // Update run time
        $rescheduledJob = $job->withRunAt($newRunAt);
        $this->repository->save($rescheduledJob);
        
        $this->logger->info('Rescheduled job', [
            'jobId' => $jobId,
            'previousRunAt' => $job->runAt->format('c'),
            'newRunAt' => $newRunAt->format('c'),
        ]);
        
        return $rescheduledJob;
    }
    
    /**
     * Get job by ID
     */
    public function getJob(string $jobId): ?ScheduledJob
    {
        return $this->repository->find($jobId);
    }
    
    /**
     * Generate a ULID
     *
     * Simple implementation - in production, use a proper ULID library
     * or delegate to a ULID generator service.
     */
    private function generateUlid(): string
    {
        // This is a simplified ULID generation
        // In production, use symfony/ulid or another proper implementation
        $timestamp = (int)($this->clock->now()->getTimestamp() * 1000);
        $timestampPart = base_convert((string)$timestamp, 10, 32);
        $randomPart = bin2hex(random_bytes(10));
        
        return strtoupper(str_pad($timestampPart, 10, '0', STR_PAD_LEFT) . substr($randomPart, 0, 16));
    }
}
