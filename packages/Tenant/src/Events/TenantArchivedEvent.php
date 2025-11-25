<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Archived Event
 *
 * Immutable value object representing a tenant archival event.
 * Dispatched when a tenant is soft-deleted/archived.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class TenantArchivedEvent
{
    /**
     * @param TenantInterface $tenant The archived tenant
     * @param string|null $reason Optional reason for archival
     */
    public function __construct(
        public TenantInterface $tenant,
        public ?string $reason = null
    ) {}
}
