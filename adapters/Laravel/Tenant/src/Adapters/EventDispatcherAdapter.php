<?php

declare(strict_types=1);

namespace Nexus\Laravel\Tenant\Adapters;

use Nexus\Tenant\Contracts\EventDispatcherInterface;
use Illuminate\Contracts\Events\Dispatcher as LaravelEventDispatcher;

/**
 * Laravel implementation of EventDispatcherInterface.
 *
 * Uses Laravel's event dispatcher for tenant-related events.
 */
class EventDispatcherAdapter implements EventDispatcherInterface
{
    public function __construct(
        private readonly LaravelEventDispatcher $dispatcher
    ) {}

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    /**
     * {@inheritdoc}
     */
    public function listen(string|array $events, callable $listener): void
    {
        $this->dispatcher->listen($events, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(object $subscriber): void
    {
        $this->dispatcher->subscribe($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function until(string $event, mixed $payload = []): mixed
    {
        return $this->dispatcher->until($event, $payload);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners(string $eventName): bool
    {
        return $this->dispatcher->hasListeners($eventName);
    }
}
