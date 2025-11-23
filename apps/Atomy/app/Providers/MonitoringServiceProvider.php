<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Monitoring\PrometheusTelemetryAdapter;
use Illuminate\Support\ServiceProvider;
use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

/**
 * MonitoringServiceProvider
 *
 * Binds universal Nexus\Monitoring contracts to concrete Prometheus implementations.
 *
 * Architecture:
 * - All Nexus packages consume TelemetryTrackerInterface (vendor-agnostic)
 * - This provider binds the interface to Prometheus implementation
 * - Switching vendors only requires changing this binding (no package changes)
 */
class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind TelemetryTrackerInterface to Prometheus adapter
        $this->app->singleton(TelemetryTrackerInterface::class, function ($app) {
            // Use Redis storage in production for shared metrics across workers
            // Use InMemory storage in testing for isolation
            $storage = $app->environment('testing')
                ? new InMemory()
                : Redis::fromExistingConnection(
                    new \Predis\Client([
                        'host' => config('eventstream.metrics.redis.host', '127.0.0.1'),
                        'port' => config('eventstream.metrics.redis.port', 63971),
                        'database' => config('eventstream.metrics.redis.database', 0),
                        'timeout' => config('eventstream.metrics.redis.timeout', 2.0),
                    ])
                );

            $registry = new CollectorRegistry($storage);

            return new PrometheusTelemetryAdapter($registry);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
