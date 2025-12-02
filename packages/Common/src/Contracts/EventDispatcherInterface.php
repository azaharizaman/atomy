<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Event Dispatcher Interface
 *
 * Defines contract for dispatching domain events to the application layer.
 * The application layer should implement this using its event system
 * (Laravel Events, Symfony EventDispatcher, etc.).
 *
 * Following Stateless Architecture: No in-memory state, delegates to application's event system.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all registered listeners.
     *
     * @param object $event Event value object to dispatch
     * @return void
     */
    public function dispatch(object $event): void;
}
