<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use App\Models\Finance\AccountBalanceProjection;

/**
 * Cache Hot Accounts Command
 * 
 * Caches the most frequently accessed account balances in Redis for ultra-fast retrieval.
 * Uses the hot-accounts sorted set to identify top accounts by access count.
 * 
 * Scheduled to run hourly via Laravel scheduler.
 */
final class CacheHotAccountsCommand extends Command
{
    protected $signature = 'finance:cache-hot-accounts
                            {--top=100 : Number of hot accounts to cache}
                            {--ttl=3600 : Cache TTL in seconds (default: 1 hour)}
                            {--clear : Clear existing cache before rebuilding}';

    protected $description = 'Cache hot account balances in Redis for fast retrieval';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $top = (int) $this->option('top');
        $ttl = (int) $this->option('ttl');
        $clear = $this->option('clear');

        if ($clear) {
            $this->info('Clearing existing hot account cache...');
            $this->clearHotAccountCache();
        }

        // Get top N accounts from hot-accounts sorted set (highest scores)
        $hotAccounts = Redis::connection('hot-accounts')
            ->zrevrange('hot_accounts', 0, $top - 1, 'WITHSCORES');

        if (empty($hotAccounts)) {
            $this->warn('No hot accounts found in Redis sorted set');
            $this->comment('Hot accounts are tracked via ZINCRBY during projection updates');
            return self::SUCCESS;
        }

        // Redis returns [account1, score1, account2, score2, ...]
        // Convert to [account => score] pairs
        $accounts = [];
        for ($i = 0; $i < count($hotAccounts); $i += 2) {
            $accounts[$hotAccounts[$i]] = (int) $hotAccounts[$i + 1];
        }

        $this->info(sprintf('Caching %d hot accounts (TTL: %d seconds)', count($accounts), $ttl));

        $bar = $this->output->createProgressBar(count($accounts));
        $bar->start();

        $cached = 0;
        $errors = 0;

        foreach ($accounts as $accountId => $accessCount) {
            try {
                // Fetch projection from database
                $projection = AccountBalanceProjection::where('account_id', $accountId)
                    ->first(['account_id', 'current_balance', 'debit_balance', 'credit_balance', 'last_event_version']);

                if ($projection) {
                    // Cache in Redis with prefix
                    $cacheKey = "hot_account_balance:{$accountId}";
                    Cache::put($cacheKey, [
                        'account_id' => $projection->account_id,
                        'current_balance' => $projection->current_balance,
                        'debit_balance' => $projection->debit_balance,
                        'credit_balance' => $projection->credit_balance,
                        'last_event_version' => $projection->last_event_version,
                        'cached_at' => now()->toIso8601String(),
                        'access_count' => $accessCount,
                    ], $ttl);

                    $cached++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to cache account {$accountId}: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf('Successfully cached %d hot accounts', $cached));

        if ($errors > 0) {
            $this->warn(sprintf('%d accounts failed to cache', $errors));
        }

        // Display top 10 hottest accounts
        $this->comment('Top 10 Hottest Accounts:');
        $topAccounts = array_slice($accounts, 0, 10, true);
        $this->table(
            ['Account ID', 'Access Count'],
            array_map(fn($id, $count) => [$id, number_format($count)], array_keys($topAccounts), $topAccounts)
        );

        return self::SUCCESS;
    }

    /**
     * Clear all hot account cache entries
     */
    private function clearHotAccountCache(): void
    {
        // Get all cache keys matching pattern using Redis facade
        $prefix = config('cache.prefix') ? config('cache.prefix') . ':' : '';
        $keys = Redis::keys($prefix . 'hot_account_balance:*');

        if (!empty($keys)) {
            foreach ($keys as $key) {
                // Remove prefix that Redis adds
                $cleanKey = str_replace($prefix, '', $key);
                Cache::forget($cleanKey);
            }

            $this->info(sprintf('Cleared %d cached accounts', count($keys)));
        }
    }
}
