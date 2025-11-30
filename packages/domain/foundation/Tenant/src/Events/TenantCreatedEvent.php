<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Created Event
 *
 * Immutable value object representing a tenant creation event.
 * The application layer should listen to this event and handle it appropriately.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class TenantCreatedEvent
{
    public function __construct(
        public TenantInterface $tenant
    ) {
    }
}
