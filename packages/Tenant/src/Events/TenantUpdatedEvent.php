<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Updated Event
 *
 * Immutable value object representing a tenant update event.
 * Dispatched when tenant data is modified.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class TenantUpdatedEvent
{
    /**
     * @param TenantInterface $tenant The updated tenant
     * @param array<string, mixed> $changes Array of changed fields with old/new values
     */
    public function __construct(
        public TenantInterface $tenant,
        public array $changes = []
    ) {}
}
