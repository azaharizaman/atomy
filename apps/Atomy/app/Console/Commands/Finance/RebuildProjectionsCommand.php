<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\Finance\RebuildAccountProjectionJob;

/**
 * Rebuild Projections Command
 * 
 * Rebuilds all AccountBalanceProjection from EventStream with parallel processing.
 * Supports worker pool configuration for high-performance rebuilds.
 */
final class RebuildProjectionsCommand extends Command
{
    protected $signature = 'finance:rebuild-projections
                            {--workers=1 : Number of parallel workers (1-20)}
                            {--account= : Rebuild specific account only}
                            {--no-snapshot : Disable snapshot optimization}
                            {--dry-run : Show what would be done without executing}';

    protected $description = 'Rebuild account balance projections from event stream with parallel processing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $workers = (int) $this->option('workers');
        $accountId = $this->option('account');
        $useSnapshot = !$this->option('no-snapshot');
        $dryRun = $this->option('dry-run');

        // Validate workers
        if ($workers < 1 || $workers > 20) {
            $this->error('Workers must be between 1 and 20');
            return self::FAILURE;
        }

        // Get accounts to rebuild
        $accounts = $accountId
            ? [$accountId]
            : $this->getAccountsWithEvents();

        if (empty($accounts)) {
            $this->info('No accounts found with events');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Rebuilding projections for %d accounts with %d workers',
            count($accounts),
            $workers
        ));

        if ($useSnapshot) {
            $this->info('Snapshot optimization: ENABLED');
        } else {
            $this->warn('Snapshot optimization: DISABLED (full replay)');
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->table(
                ['Account ID', 'Event Count'],
                array_map(fn($acc) => [$acc, $this->getEventCount($acc)], $accounts)
            );
            return self::SUCCESS;
        }

        // Dispatch jobs
        $bar = $this->output->createProgressBar(count($accounts));
        $bar->start();

        $batches = array_chunk($accounts, $workers);
        $totalJobs = 0;

        foreach ($batches as $batch) {
            foreach ($batch as $account) {
                RebuildAccountProjectionJob::dispatch($account, $useSnapshot);
                $totalJobs++;
                $bar->advance();
            }

            // Brief pause between batches to avoid overwhelming queue
            if (count($batches) > 1) {
                usleep(100000); // 100ms
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Dispatched %d rebuild jobs to finance-projections queue',
            $totalJobs
        ));

        $this->comment('Monitor progress with: php artisan queue:work redis --queue=finance-projections');

        return self::SUCCESS;
    }

    /**
     * Get all account IDs that have events in the stream
     * 
     * @return array<string>
     */
    private function getAccountsWithEvents(): array
    {
        return DB::table('event_streams')
            ->where('aggregate_type', 'account')
            ->distinct()
            ->pluck('aggregate_id')
            ->toArray();
    }

    /**
     * Get event count for an account
     */
    private function getEventCount(string $accountId): int
    {
        return DB::table('event_streams')
            ->where('aggregate_type', 'account')
            ->where('aggregate_id', $accountId)
            ->count();
    }
}
