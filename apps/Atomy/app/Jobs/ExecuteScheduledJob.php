<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nexus\Scheduler\Services\ScheduleManager;

/**
 * Execute Scheduled Job
 *
 * Laravel queue job that executes a scheduled job via ScheduleManager.
 */
class ExecuteScheduledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1; // Retries managed by ScheduleManager
    
    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes
    
    /**
     * @param string $jobId Scheduled job ULID
     */
    public function __construct(
        public readonly string $jobId
    ) {}
    
    /**
     * Execute the job
     */
    public function handle(ScheduleManager $scheduler): void
    {
        $scheduler->executeJob($this->jobId);
    }
}
