<?php

declare(strict_types=1);

namespace Nexus\Tenant\Events;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Impersonation Ended Event
 *
 * Immutable value object representing the end of tenant impersonation.
 * Dispatched when a user/admin stops impersonating and returns to original context.
 *
 * @package Nexus\Tenant\Events
 */
final readonly class ImpersonationEndedEvent
{
    /**
     * @param TenantInterface $targetTenant The tenant that was being impersonated
     * @param TenantInterface $restoredTenant The original tenant context being restored
     * @param string|null $impersonatorId ID of user who was performing impersonation
     */
    public function __construct(
        public TenantInterface $targetTenant,
        public TenantInterface $restoredTenant,
        public ?string $impersonatorId = null
    ) {}
}
