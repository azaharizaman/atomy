<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Laravel integration for {@see \Nexus\Idempotency} (bindings registered in later tasks).
 */
final class IdempotencyAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
