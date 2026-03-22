<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;
use Nexus\Idempotency\Contracts\IdempotencyPersistInterface;
use Nexus\Idempotency\Contracts\IdempotencyQueryInterface;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use Nexus\Idempotency\Domain\IdempotencyPolicy;
use Nexus\Idempotency\Services\IdempotencyService;
use Nexus\Laravel\Idempotency\Adapters\DatabaseIdempotencyStore;
use Nexus\Laravel\Idempotency\Clock\LaravelIdempotencyClock;
use Nexus\Laravel\Idempotency\Console\Commands\IdempotencyCleanupCommand;
use Nexus\Laravel\Idempotency\Support\IdempotencyPolicyFactory;
use Nexus\Laravel\Idempotency\Support\RequestFingerprintFactory;

final class IdempotencyAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/nexus-idempotency.php', 'nexus-idempotency');

        $this->app->singleton(DatabaseIdempotencyStore::class);
        $this->app->singleton(IdempotencyStoreInterface::class, static fn ($app) => $app->make(DatabaseIdempotencyStore::class));
        $this->app->singleton(IdempotencyQueryInterface::class, static fn ($app) => $app->make(IdempotencyStoreInterface::class));
        $this->app->singleton(IdempotencyPersistInterface::class, static fn ($app) => $app->make(IdempotencyStoreInterface::class));

        $this->app->singleton(IdempotencyClockInterface::class, LaravelIdempotencyClock::class);
        $this->app->singleton(IdempotencyPolicyFactory::class);
        $this->app->singleton(RequestFingerprintFactory::class);

        $this->app->singleton(IdempotencyPolicy::class, function ($app): IdempotencyPolicy {
            /** @var array<string, mixed> $policy */
            $policy = $app['config']->get('nexus-idempotency.policy', []);

            return $app->make(IdempotencyPolicyFactory::class)->make($policy);
        });

        $this->app->singleton(IdempotencyServiceInterface::class, function ($app): IdempotencyServiceInterface {
            return new IdempotencyService(
                $app->make(IdempotencyQueryInterface::class),
                $app->make(IdempotencyPersistInterface::class),
                $app->make(IdempotencyClockInterface::class),
                $app->make(IdempotencyPolicy::class),
            );
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                IdempotencyCleanupCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'nexus-idempotency-migrations');

        $this->publishes([
            __DIR__ . '/../../config/nexus-idempotency.php' => config_path('nexus-idempotency.php'),
        ], 'nexus-idempotency-config');
    }
}
