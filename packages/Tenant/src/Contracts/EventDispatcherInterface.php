<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Event Dispatcher Interface
 *
 * Defines contract for dispatching tenant lifecycle events to the application layer.
 * The application layer should implement this using its event system (Laravel Events, Symfony EventDispatcher, etc.).
 *
 * Following Stateless Architecture: No in-memory state, delegates to application's event system.
 *
 * @package Nexus\Tenant\Contracts
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
