<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Activated Event
 *
 * Immutable value object representing a tenant activation event.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class TenantActivatedEvent
{
    public function __construct(
        public TenantInterface $tenant
    ) {
    }
}
