<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Nexus\Finance\Events\AccountDebitedEvent;
use Nexus\Finance\Events\AccountCreditedEvent;
use App\Listeners\UpdateAccountBalanceProjection;

/**
 * Event Service Provider
 * 
 * Registers event listeners for Finance domain.
 * All listeners are queued on the finance-projections queue.
 */
final class EventServiceProvider extends ServiceProvider
{
    /**
     * Event listener mappings
     * 
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AccountDebitedEvent::class => [
            UpdateAccountBalanceProjection::class,
        ],
        AccountCreditedEvent::class => [
            UpdateAccountBalanceProjection::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // Explicit registration for clarity
    }
}
