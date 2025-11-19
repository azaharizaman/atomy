<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Contracts;

use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Job Queue Interface
 *
 * Abstracts queue/job dispatching mechanism.
 * Implemented by the application layer (e.g., LaravelJobQueue).
 */
interface JobQueueInterface
{
    /**
     * Dispatch a job to the queue
     *
     * @param ScheduledJob $job The job to dispatch
     * @param int|null $delaySeconds Delay before processing (null = immediate)
     * @return void
     */
    public function dispatch(ScheduledJob $job, ?int $delaySeconds = null): void;
    
    /**
     * Get queue size for monitoring
     *
     * @return int Number of pending jobs in queue
     */
    public function size(): int;
}
