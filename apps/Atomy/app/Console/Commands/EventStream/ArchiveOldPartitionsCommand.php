<?php

declare(strict_types=1);

namespace App\Console\Commands\EventStream;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Archive old event_streams partitions to S3/Azure Blob storage.
 * 
 * Scheduled to run monthly. Archives partitions older than 7 years.
 * 
 * Process:
 * 1. Identify partitions older than retention period (7 years)
 * 2. Export partition data to JSONL format
 * 3. Compress and upload to configured storage (S3/Azure Blob)
 * 4. Detach partition from parent table
 * 5. Drop detached partition
 */
class ArchiveOldPartitionsCommand extends Command
{
    protected $signature = 'eventstream:archive-old-partitions
                            {--dry-run : Show what would be archived without executing}
                            {--retention-years=7 : Number of years to retain (default: 7)}';

    protected $description = 'Archive event_streams partitions older than retention period (default: 7 years)';

    public function handle(): int
    {
        $retentionYears = (int) $this->option('retention-years');
        $cutoffDate = Carbon::today()->subYears($retentionYears);
        $cutoffYear = $cutoffDate->year;
        
        $this->info("Archival Policy: {$retentionYears} years retention");
        $this->line("Cutoff Date: {$cutoffDate->toDateString()}");
        $this->line("Partitions before {$cutoffYear} will be archived.");
        $this->newLine();
        
        // Find all event_streams partitions
        $partitions = DB::select("
            SELECT 
                c.relname as partition_name,
                pg_get_expr(c.relpartbound, c.oid) as partition_bounds
            FROM pg_class c
            JOIN pg_inherits i ON i.inhrelid = c.oid
            JOIN pg_class p ON p.oid = i.inhparent
            WHERE p.relname = 'event_streams'
            AND c.relname LIKE 'event_streams_%'
            ORDER BY c.relname
        ");
        
        $archivedCount = 0;
        $skippedCount = 0;
        
        foreach ($partitions as $partition) {
            // Extract year from partition name (e.g., event_streams_2018)
            if (!preg_match('/event_streams_(\d{4})/', $partition->partition_name, $matches)) {
                $this->warn("Skipping invalid partition name: {$partition->partition_name}");
                $skippedCount++;
                continue;
            }
            
            $partitionYear = (int) $matches[1];
            
            if ($partitionYear >= $cutoffYear) {
                $this->line("✓ Keeping {$partition->partition_name} (within retention period)");
                $skippedCount++;
                continue;
            }
            
            // Partition is older than retention period - archive it
            if ($this->option('dry-run')) {
                $this->warn("[DRY RUN] Would archive: {$partition->partition_name} (year {$partitionYear})");
                $archivedCount++;
                continue;
            }
            
            try {
                $this->archivePartition($partition->partition_name, $partitionYear);
                $archivedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to archive {$partition->partition_name}: {$e->getMessage()}");
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->line("  Archived: {$archivedCount}");
        $this->line("  Kept: {$skippedCount}");
        
        return self::SUCCESS;
    }

    private function archivePartition(string $partitionName, int $year): void
    {
        $this->warn("Archiving {$partitionName}...");
        
        // 1. Count records
        $count = DB::selectOne("SELECT COUNT(*) as count FROM {$partitionName}")->count;
        $this->line("  Records: {$count}");
        
        if ($count === 0) {
            $this->line("  Empty partition - skipping export");
        } else {
            // 2. Export to JSONL
            $exportPath = storage_path("app/eventstream-archive/{$partitionName}.jsonl");
            $this->line("  Exporting to: {$exportPath}");
            
            DB::statement("
                COPY (SELECT row_to_json(t) FROM {$partitionName} t) 
                TO '{$exportPath}'
            ");
            
            // 3. Compress
            $compressedPath = "{$exportPath}.gz";
            exec("gzip -9 {$exportPath}");
            $this->line("  Compressed: " . filesize($compressedPath) . " bytes");
            
            // 4. Upload to storage
            $storageDisk = config('eventstream.archive.storage_disk', 's3');
            $storageKey = "eventstream-archive/{$year}/{$partitionName}.jsonl.gz";
            
            Storage::disk($storageDisk)->put($storageKey, file_get_contents($compressedPath));
            $this->line("  Uploaded to {$storageDisk}: {$storageKey}");
            
            // 5. Cleanup local file
            unlink($compressedPath);
        }
        
        // 6. Detach partition
        DB::statement("ALTER TABLE event_streams DETACH PARTITION {$partitionName}");
        $this->line("  Detached from parent table");
        
        // 7. Drop partition
        DB::statement("DROP TABLE {$partitionName}");
        $this->info("✓ Archived and dropped {$partitionName}");
        
        // 8. Log action
        $this->call('eventstream:log-partition-action', [
            'action' => 'archived',
            'partition' => $partitionName,
            'record_count' => $count,
            'storage_key' => $storageKey ?? null,
        ]);
    }
}
