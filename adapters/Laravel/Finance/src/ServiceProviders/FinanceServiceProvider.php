<?php

declare(strict_types=1);

namespace Nexus\Laravel\Finance\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Nexus\Finance\Domain\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Domain\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Domain\Contracts\LedgerRepositoryInterface;
use Nexus\Laravel\Finance\Repositories\EloquentAccountRepository;
use Nexus\Laravel\Finance\Repositories\EloquentJournalEntryRepository;
use Nexus\Laravel\Finance\Repositories\EloquentLedgerRepository;

/**
 * Laravel Service Provider for Nexus Finance Package
 * 
 * Registers Eloquent implementations of Finance repository interfaces.
 * This provider bridges the framework-agnostic Finance package with Laravel.
 */
class FinanceServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register Eloquent repository implementations
        $this->app->bind(
            AccountRepositoryInterface::class,
            EloquentAccountRepository::class
        );

        $this->app->bind(
            JournalEntryRepositoryInterface::class,
            EloquentJournalEntryRepository::class
        );

        $this->app->bind(
            LedgerRepositoryInterface::class,
            EloquentLedgerRepository::class
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish migrations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Migrations' => database_path('migrations'),
            ], 'nexus-finance-migrations');
        }
    }
}
