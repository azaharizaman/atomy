<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use Nexus\Laravel\Idempotency\Adapters\DatabaseIdempotencyStore;
use Nexus\Laravel\Idempotency\Clock\LaravelIdempotencyClock;
use Nexus\Laravel\Idempotency\Http\IdempotencyMiddleware;
use Nexus\Laravel\Idempotency\Models\IdempotencyRecord;
use Nexus\Laravel\Idempotency\Support\IdempotencyPolicyFactory;

final class IdempotencyAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/nexus-idempotency.php',
            'nexus-idempotency'
        );

        $this->app->singleton(IdempotencyClockInterface::class, LaravelIdempotencyClock::class);

        $this->app->singleton(IdempotencyPolicyFactory::class);

        $this->app->singleton(IdempotencyStoreInterface::class, function ($app) {
            $store = config('nexus-idempotency.store', 'database');
            
            if ($store === 'database') {
                return new DatabaseIdempotencyStore(
                    new IdempotencyRecord()
                );
            }

            throw new \RuntimeException("Unsupported idempotency store: {$store}");
        });

        $this->app->singleton(IdempotencyServiceInterface::class, function ($app) {
            $store = $app->make(IdempotencyStoreInterface::class);
            
            return new \Nexus\Idempotency\Services\IdempotencyService(
                $store,
                $store,
                $app->make(IdempotencyClockInterface::class),
                $app->make(IdempotencyPolicyFactory::class)->make()
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/nexus-idempotency.php' => config_path('nexus-idempotency.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if (config('nexus-idempotency.middleware.enabled', true)) {
            $router = $this->app->make(Router::class);
            $router->aliasMiddleware('idempotency', IdempotencyMiddleware::class);
        }
    }
}
