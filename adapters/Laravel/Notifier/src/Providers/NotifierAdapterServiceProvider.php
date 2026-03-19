<?php

declare(strict_types=1);

namespace Nexus\Laravel\Notifier\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Laravel\Notifier\Adapters\PostmarkEmailAdapter;
use Nexus\Laravel\Notifier\Services\LaravelNotificationManager;
use Nexus\Notifier\Contracts\NotificationManagerInterface;

final class NotifierAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/notifier-adapter.php', 'notifier-adapter');

        $this->app->singleton(PostmarkEmailAdapter::class, function ($app): PostmarkEmailAdapter {
            return new PostmarkEmailAdapter(
                mailer: $app['mailer'],
                config: (array) $app['config']->get('notifier-adapter', []),
                logger: $app['log'],
            );
        });

        $this->app->singleton(NotificationManagerInterface::class, function ($app): NotificationManagerInterface {
            return new LaravelNotificationManager(
                queue: $app['queue'],
                config: (array) $app['config']->get('notifier-adapter', []),
                logger: $app['log'],
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'nexus-notifier');

        $this->publishes([
            __DIR__ . '/../../config/notifier-adapter.php' => config_path('notifier-adapter.php'),
        ], 'config');
    }
}

