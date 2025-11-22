<?php

declare(strict_types=1);

namespace App\Console\Commands\EventStream;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Create next fiscal year partition for event_streams.
 * 
 * Scheduled to run daily. Creates partition 30 days before fiscal year starts.
 * 
 * Example: On December 2, 2025, creates event_streams_2026 partition.
 */
class CreateNextYearPartitionCommand extends Command
{
    protected $signature = 'eventstream:create-next-partition
                            {--dry-run : Show what would be created without executing}';

    protected $description = 'Create next fiscal year partition for event_streams (30-day pre-creation)';

    public function handle(): int
    {
        $today = Carbon::today();
        $nextYear = $today->copy()->addYear()->year;
        $nextYearStart = Carbon::create($nextYear, 1, 1);
        $nextYearEnd = Carbon::create($nextYear + 1, 1, 1);
        
        // Check if we're within 30-day window
        $daysUntilNextYear = $today->diffInDays($nextYearStart, false);
        
        if ($daysUntilNextYear > 30) {
            $this->info("Next fiscal year is {$daysUntilNextYear} days away. Partition will be created on " . $nextYearStart->copy()->subDays(30)->toDateString());
            return self::SUCCESS;
        }
        
        if ($daysUntilNextYear < 0) {
            $this->warn("Next fiscal year already started. Creating partition for {$nextYear}.");
        }
        
        $partitionName = "event_streams_{$nextYear}";
        
        // Check if partition already exists
        $exists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM pg_tables 
                WHERE schemaname = 'public' AND tablename = ?
            ) as exists
        ", [$partitionName]);
        
        if ($exists->exists) {
            $this->warn("Partition {$partitionName} already exists.");
            return self::SUCCESS;
        }
        
        if ($this->option('dry-run')) {
            $this->info("[DRY RUN] Would create partition: {$partitionName}");
            $this->line("  Range: {$nextYearStart->toDateString()} to {$nextYearEnd->toDateString()}");
            return self::SUCCESS;
        }
        
        // Create partition
        try {
            DB::statement("
                CREATE TABLE {$partitionName} PARTITION OF event_streams
                FOR VALUES FROM ('{$nextYearStart->toDateTimeString()}') TO ('{$nextYearEnd->toDateTimeString()}')
            ");
            
            $this->info("✓ Created partition {$partitionName}");
            $this->line("  Range: {$nextYearStart->toDateString()} to {$nextYearEnd->toDateString()}");
            
            // Log to audit
            $this->call('eventstream:log-partition-action', [
                'action' => 'created',
                'partition' => $partitionName,
                'range_start' => $nextYearStart->toDateTimeString(),
                'range_end' => $nextYearEnd->toDateTimeString(),
            ]);
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create partition {$partitionName}: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
