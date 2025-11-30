<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Suspended Event
 *
 * Immutable value object representing a tenant suspension event.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class TenantSuspendedEvent
{
    public function __construct(
        public TenantInterface $tenant,
        public ?string $reason = null
    ) {
    }
}
