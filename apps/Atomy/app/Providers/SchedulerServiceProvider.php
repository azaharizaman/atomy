<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use App\Repositories\DbScheduleRepository;
use App\Services\LaravelJobQueue;
use App\Services\NullCalendarExporter;
use App\Services\SystemClock;
use Nexus\Scheduler\Contracts\CalendarExporterInterface;
use Nexus\Scheduler\Contracts\ClockInterface;
use Nexus\Scheduler\Contracts\JobQueueInterface;
use Nexus\Scheduler\Contracts\ScheduleManagerInterface;
use Nexus\Scheduler\Contracts\ScheduleRepositoryInterface;
use Nexus\Scheduler\Services\ScheduleManager;

/**
 * Scheduler Service Provider
 *
 * Binds Nexus\Scheduler interfaces to Atomy implementations.
 * Registers tagged handler discovery for domain job handlers.
 */
class SchedulerServiceProvider extends ServiceProvider
{
    /**
     * Register scheduler services.
     */
    public function register(): void
    {
        // Bind infrastructure interfaces
        $this->app->singleton(ScheduleRepositoryInterface::class, DbScheduleRepository::class);
        $this->app->singleton(JobQueueInterface::class, LaravelJobQueue::class);
        $this->app->singleton(ClockInterface::class, SystemClock::class);
        $this->app->singleton(CalendarExporterInterface::class, NullCalendarExporter::class);
        
        // Bind ScheduleManager with tagged handlers
        $this->app->singleton(ScheduleManagerInterface::class, function ($app) {
            return new ScheduleManager(
                repository: $app->make(ScheduleRepositoryInterface::class),
                queue: $app->make(JobQueueInterface::class),
                clock: $app->make(ClockInterface::class),
                handlers: $app->tagged('scheduler.handlers'),
                logger: $app->make(LoggerInterface::class)
            );
        });
        
        // Also bind concrete class for direct injection
        $this->app->singleton(ScheduleManager::class, function ($app) {
            return $app->make(ScheduleManagerInterface::class);
        });
    }
    
    /**
     * Bootstrap scheduler services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Publish configuration if needed
        // $this->publishes([
        //     __DIR__ . '/../../config/scheduler.php' => config_path('scheduler.php'),
        // ], 'scheduler-config');
    }
}
