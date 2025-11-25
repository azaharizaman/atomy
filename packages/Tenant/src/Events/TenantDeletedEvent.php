<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Deleted Event
 *
 * Immutable value object representing a tenant deletion event.
 * Dispatched when a tenant is permanently deleted.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class TenantDeletedEvent
{
    /**
     * @param TenantInterface $tenant The deleted tenant
     * @param bool $force Whether this was a force delete
     */
    public function __construct(
        public TenantInterface $tenant,
        public bool $force = false
    ) {}
}
