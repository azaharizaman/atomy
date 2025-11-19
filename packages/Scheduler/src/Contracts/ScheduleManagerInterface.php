<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Contracts;

use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Schedule Manager Interface
 *
 * Main service for managing scheduled jobs.
 * Orchestrates scheduling, execution, and lifecycle management.
 */
interface ScheduleManagerInterface
{
    /**
     * Schedule a new job
     *
     * @param ScheduleDefinition $definition Job definition
     * @return ScheduledJob The created scheduled job
     */
    public function schedule(ScheduleDefinition $definition): ScheduledJob;
    
    /**
     * Execute a scheduled job by ID
     *
     * This method:
     * 1. Finds the appropriate handler
     * 2. Invokes ExecutionEngine
     * 3. Updates job status
     * 4. Handles retry logic
     *
     * @param string $jobId Job ULID
     * @return JobResult Execution result
     */
    public function executeJob(string $jobId): JobResult;
    
    /**
     * Cancel a scheduled job
     *
     * @param string $jobId Job ULID
     * @return bool True if canceled, false if not found or already final
     */
    public function cancelJob(string $jobId): bool;
    
    /**
     * Get all jobs that are due for execution
     *
     * @return ScheduledJob[] Array of due jobs
     */
    public function getDueJobs(): array;
    
    /**
     * Reschedule a job with new execution time
     *
     * @param string $jobId Job ULID
     * @param \DateTimeImmutable $newRunAt New execution time
     * @return ScheduledJob Updated job
     */
    public function rescheduleJob(string $jobId, \DateTimeImmutable $newRunAt): ScheduledJob;
    
    /**
     * Get job by ID
     *
     * @param string $jobId Job ULID
     * @return ScheduledJob|null The job or null if not found
     */
    public function getJob(string $jobId): ?ScheduledJob;
}
