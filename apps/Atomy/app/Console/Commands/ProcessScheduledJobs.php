<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Scheduler\Services\ScheduleManager;

/**
 * Process Scheduled Jobs Command
 *
 * Retrieves due jobs and dispatches them to the queue.
 * Should be run via cron every minute.
 *
 * Add to crontab:
 * * * * * * php artisan schedule:process
 */
class ProcessScheduledJobs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'schedule:process
                          {--limit=100 : Maximum number of jobs to process}
                          {--dry-run : Display jobs without dispatching}';
    
    /**
     * The console command description.
     */
    protected $description = 'Process scheduled jobs that are due for execution';
    
    /**
     * Execute the console command.
     */
    public function handle(ScheduleManager $scheduler): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        
        $this->info('Checking for due scheduled jobs...');
        
        $dueJobs = $scheduler->getDueJobs();
        $jobCount = count($dueJobs);
        
        if ($jobCount === 0) {
            $this->info('No jobs are due for execution.');
            return self::SUCCESS;
        }
        
        // Limit number of jobs to process
        $jobsToProcess = array_slice($dueJobs, 0, $limit);
        $processCount = count($jobsToProcess);
        
        $this->info("Found {$jobCount} due job(s). Processing {$processCount}...");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - Jobs will not be dispatched');
            $this->displayJobs($jobsToProcess);
            return self::SUCCESS;
        }
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($jobsToProcess as $job) {
            try {
                $this->line("Dispatching job {$job->id} ({$job->jobType->label()})...");
                $scheduler->executeJob($job->id);
                $successCount++;
            } catch (\Throwable $e) {
                $this->error("Failed to dispatch job {$job->id}: {$e->getMessage()}");
                $failCount++;
            }
        }
        
        $this->newLine();
        $this->info("Processing complete:");
        $this->line("  ✓ Successfully dispatched: {$successCount}");
        
        if ($failCount > 0) {
            $this->line("  ✗ Failed: {$failCount}");
        }
        
        if ($jobCount > $processCount) {
            $remaining = $jobCount - $processCount;
            $this->warn("  ⚠ {$remaining} job(s) remain (increase --limit to process more)");
        }
        
        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }
    
    /**
     * Display jobs in table format
     *
     * @param \Nexus\Scheduler\ValueObjects\ScheduledJob[] $jobs
     */
    private function displayJobs(array $jobs): void
    {
        $rows = array_map(function ($job) {
            return [
                $job->id,
                $job->jobType->label(),
                $job->targetId,
                $job->runAt->format('Y-m-d H:i:s'),
                $job->status->label(),
                $job->priority,
            ];
        }, $jobs);
        
        $this->table(
            ['ID', 'Type', 'Target', 'Run At', 'Status', 'Priority'],
            $rows
        );
    }
}
