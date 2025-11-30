<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Reactivated Event
 *
 * Immutable value object representing a tenant reactivation event.
 * Dispatched when a suspended tenant is reactivated.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class TenantReactivatedEvent
{
    /**
     * @param TenantInterface $tenant The reactivated tenant
     */
    public function __construct(
        public TenantInterface $tenant
    ) {}
}
