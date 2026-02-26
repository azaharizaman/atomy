<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

use Nexus\Telemetry\ValueObjects\AlertContext;

/**
 * Alert Dispatcher Interface
 *
 * Contract for dispatching alerts to notification systems.
 * Implementations can be synchronous (direct) or asynchronous (queued).
 *
 * @package Nexus\Telemetry\Contracts
 */
interface AlertDispatcherInterface
{
    /**
     * Dispatch an alert for delivery.
     *
     * @param AlertContext $alert Alert context with enriched data
     * @return void
     */
    public function dispatch(AlertContext $alert): void;
}
