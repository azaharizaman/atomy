<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tenant Context Service.
 *
 * Manages the current tenant context within the request lifecycle.
 * Can be set from JWT token, API header, or explicitly.
 */
final class TenantContext
{
    private ?string $currentTenantId = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security
    ) {}

    /**
     * Set the current active tenant.
     */
    public function setTenant(string $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    /**
     * Get the current active tenant ID.
     *
     * Priority:
     * 1. Explicitly set tenant ID
     * 2. X-Tenant-ID header from request
     * 3. Tenant ID from authenticated user
     */
    public function getCurrentTenantId(): ?string
    {
        // Return if explicitly set
        if ($this->currentTenantId !== null) {
            return $this->currentTenantId;
        }

        // Try to get from request header
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $headerTenantId = $request->headers->get('X-Tenant-ID');
            if ($headerTenantId !== null) {
                return $headerTenantId;
            }
        }

        // Try to get from authenticated user
        $user = $this->security->getUser();
        if ($user !== null && method_exists($user, 'getTenantId')) {
            $userTenantId = $user->getTenantId();
            if ($userTenantId !== null) {
                return $userTenantId;
            }
        }

        return null;
    }

    /**
     * Check if a tenant context is currently set.
     */
    public function hasTenant(): bool
    {
        return $this->getCurrentTenantId() !== null;
    }

    /**
     * Clear the current tenant context.
     */
    public function clearTenant(): void
    {
        $this->currentTenantId = null;
    }

    /**
     * Require that a tenant context is set, throw exception if not.
     *
     * @throws \RuntimeException if tenant context is not set
     */
    public function requireTenant(): string
    {
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context is required but not set');
        }

        return $tenantId;
    }
}
