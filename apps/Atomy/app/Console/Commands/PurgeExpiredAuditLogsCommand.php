<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\AuditLogger\Services\RetentionPolicyService;

/**
 * Command to purge expired audit logs
 * Satisfies: BUS-AUD-0151 (automated purging of expired logs)
 *
 * @package App\Console\Commands
 */
class PurgeExpiredAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:purge-expired
                          {--dry-run : Preview what would be deleted without actually deleting}
                          {--before= : Purge logs expired before this date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge expired audit logs based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(RetentionPolicyService $service): int
    {
        $this->info('Starting audit log purge process...');

        $beforeDate = $this->option('before') 
            ? new \DateTime($this->option('before'))
            : null;

        if ($this->option('dry-run')) {
            $logs = $service->previewExpiredLogs($beforeDate, 100);
            $count = $service->countExpiredLogs($beforeDate);

            $this->warn("DRY RUN MODE - No logs will be deleted");
            $this->info("Found {$count} expired logs that would be deleted.");

            if (!empty($logs)) {
                $this->info("Preview of first 100 logs to be deleted:");
                $this->table(
                    ['ID', 'Log Name', 'Description', 'Created At', 'Expires At'],
                    array_map(fn($log) => [
                        $log->getId(),
                        $log->getLogName(),
                        substr($log->getDescription(), 0, 50),
                        $log->getCreatedAt()->format('Y-m-d H:i:s'),
                        $log->getExpiresAt()->format('Y-m-d H:i:s'),
                    ], $logs)
                );
            }

            return Command::SUCCESS;
        }

        // Confirm before purging
        if (!$this->confirm('Are you sure you want to purge expired audit logs?')) {
            $this->info('Purge cancelled.');
            return Command::FAILURE;
        }

        $batchSize = config('audit.purge_batch_size', 1000);
        $deleted = $service->purgeExpiredLogs($beforeDate, $batchSize);

        $this->info("Successfully purged {$deleted} expired audit logs.");

        return Command::SUCCESS;
    }
}
