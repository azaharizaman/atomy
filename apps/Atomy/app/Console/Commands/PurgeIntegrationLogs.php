<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Connector\Contracts\IntegrationLoggerInterface;

class PurgeIntegrationLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connector:purge-logs {--days= : Number of days to retain (default from config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge old integration logs based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(IntegrationLoggerInterface $logger): int
    {
        $retentionDays = $this->option('days') 
            ?? config('connector.log_retention_days', 90);

        $this->info("Purging integration logs older than {$retentionDays} days...");

        $deleted = $logger->purgeOldLogs((int) $retentionDays);

        $this->info("Purged {$deleted} integration log(s).");

        return self::SUCCESS;
    }
}
