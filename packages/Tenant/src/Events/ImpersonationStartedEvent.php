<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Impersonation Started Event
 *
 * Immutable value object representing the start of tenant impersonation.
 * Dispatched when a user/admin begins impersonating a tenant.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class ImpersonationStartedEvent
{
    /**
     * @param TenantInterface $originalTenant The original tenant context
     * @param TenantInterface $targetTenant The tenant being impersonated
     * @param string|null $impersonatorId ID of user performing impersonation
     */
    public function __construct(
        public TenantInterface $originalTenant,
        public TenantInterface $targetTenant,
        public ?string $impersonatorId = null
    ) {}
}
