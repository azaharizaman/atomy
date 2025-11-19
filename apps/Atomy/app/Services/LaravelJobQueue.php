<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Queue;
use App\Jobs\ExecuteScheduledJob;
use Nexus\Scheduler\Contracts\JobQueueInterface;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Laravel Job Queue
 *
 * Laravel Queue implementation of JobQueueInterface.
 * Wraps Laravel's queue system for scheduled job execution.
 */
final readonly class LaravelJobQueue implements JobQueueInterface
{
    /**
     * Dispatch a job to the queue
     */
    public function dispatch(ScheduledJob $job, ?int $delaySeconds = null): void
    {
        $laravelJob = new ExecuteScheduledJob($job->id);
        
        if ($delaySeconds !== null && $delaySeconds > 0) {
            Queue::later(now()->addSeconds($delaySeconds), $laravelJob);
        } else {
            Queue::push($laravelJob);
        }
    }
    
    /**
     * Get queue size for monitoring
     */
    public function size(): int
    {
        // Note: Queue size monitoring depends on queue driver
        // This is a simplified implementation
        // For production, implement driver-specific logic
        return 0;
    }
}
