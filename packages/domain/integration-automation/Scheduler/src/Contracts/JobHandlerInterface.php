<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Contracts;

use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Job Handler Interface
 *
 * Implemented by domain packages to execute specific job types.
 * Handlers signal retry intent via JobResult.
 * ExecutionEngine manages the actual retry mechanism.
 */
interface JobHandlerInterface
{
    /**
     * Check if this handler supports the given job type
     */
    public function supports(JobType $jobType): bool;
    
    /**
     * Execute the scheduled job
     *
     * Handler responsibilities:
     * - Execute domain logic
     * - Return JobResult with success/failure
     * - Signal retry intent (shouldRetry, retryDelaySeconds)
     *
     * Handler MUST NOT:
     * - Update job status directly
     * - Manage retry logic
     * - Handle queue dispatching
     *
     * @param ScheduledJob $job The job to execute
     * @return JobResult Execution outcome with retry intent
     */
    public function handle(ScheduledJob $job): JobResult;
}
