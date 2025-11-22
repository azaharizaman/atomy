<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Finance\Contracts\CacheInterface;
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Nexus\Finance\Services\FinanceManager;
use App\Repositories\Finance\DbAccountRepository;
use App\Repositories\Finance\DbJournalEntryRepository;
use App\Repositories\Finance\DbLedgerRepository;
use App\Services\Finance\RedisCacheAdapter;

/**
 * Finance Service Provider
 * 
 * Binds Finance package contracts to Atomy implementations.
 */
final class FinanceServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(AccountRepositoryInterface::class, DbAccountRepository::class);
        $this->app->singleton(JournalEntryRepositoryInterface::class, DbJournalEntryRepository::class);
        $this->app->singleton(LedgerRepositoryInterface::class, DbLedgerRepository::class);

        // Bind cache adapter
        $this->app->singleton(CacheInterface::class, RedisCacheAdapter::class);

        // Bind service (all dependencies auto-resolved)
        $this->app->singleton(FinanceManagerInterface::class, FinanceManager::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}
