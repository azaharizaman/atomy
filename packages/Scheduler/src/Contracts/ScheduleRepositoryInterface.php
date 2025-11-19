<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Contracts;

use DateTimeImmutable;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Schedule Repository Interface
 *
 * Manages persistence of scheduled jobs.
 * Implemented by the application layer (e.g., DbScheduleRepository with Eloquent).
 */
interface ScheduleRepositoryInterface
{
    /**
     * Find a job by ID
     *
     * @param string $id Job ULID
     * @return ScheduledJob|null The job or null if not found
     */
    public function find(string $id): ?ScheduledJob;
    
    /**
     * Find all due jobs at the given time
     *
     * @param DateTimeImmutable $asOf Check if jobs are due as of this time
     * @return ScheduledJob[] Array of due jobs
     */
    public function findDue(DateTimeImmutable $asOf): array;
    
    /**
     * Find jobs by type
     *
     * @param JobType $jobType The job type to filter by
     * @return ScheduledJob[] Array of matching jobs
     */
    public function findByType(JobType $jobType): array;
    
    /**
     * Find jobs by target entity ID
     *
     * @param string $targetId ULID of the target entity
     * @return ScheduledJob[] Array of jobs for this target
     */
    public function findByTarget(string $targetId): array;
    
    /**
     * Find jobs by status
     *
     * @param JobStatus $status The status to filter by
     * @param int $limit Maximum number of results
     * @return ScheduledJob[] Array of matching jobs
     */
    public function findByStatus(JobStatus $status, int $limit = 100): array;
    
    /**
     * Save a scheduled job
     *
     * @param ScheduledJob $job The job to persist
     * @return void
     */
    public function save(ScheduledJob $job): void;
    
    /**
     * Delete a scheduled job
     *
     * @param string $id Job ULID
     * @return bool True if deleted, false if not found
     */
    public function delete(string $id): bool;
    
    /**
     * Count jobs by status
     *
     * @param JobStatus|null $status Filter by status (null = all)
     * @return int Number of jobs
     */
    public function count(?JobStatus $status = null): int;
}
