<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Contracts;

use DateTimeImmutable;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Job Finder Interface
 *
 * Dedicated interface for querying scheduled jobs.
 * Separates query operations from command operations.
 */
interface JobFinderInterface
{
    /**
     * Find overdue jobs
     *
     * @param DateTimeImmutable $asOf Check as of this time
     * @param int $minutesOverdue Minimum minutes past runAt
     * @return ScheduledJob[] Array of overdue jobs
     */
    public function findOverdue(DateTimeImmutable $asOf, int $minutesOverdue = 5): array;
    
    /**
     * Find jobs nearing execution
     *
     * @param DateTimeImmutable $asOf Check as of this time
     * @param int $minutesBefore Minutes before runAt
     * @return ScheduledJob[] Array of jobs nearing execution
     */
    public function findNearingExecution(DateTimeImmutable $asOf, int $minutesBefore = 5): array;
    
    /**
     * Find failed jobs that can be retried
     *
     * @return ScheduledJob[] Array of retriable failed jobs
     */
    public function findRetriableFailures(): array;
    
    /**
     * Find recurring jobs
     *
     * @param JobStatus|null $status Filter by status (null = all)
     * @return ScheduledJob[] Array of recurring jobs
     */
    public function findRecurring(?JobStatus $status = null): array;
    
    /**
     * Find jobs by target and type
     *
     * @param string $targetId Target entity ULID
     * @param JobType $jobType Job type
     * @return ScheduledJob[] Array of matching jobs
     */
    public function findByTargetAndType(string $targetId, JobType $jobType): array;
}
