<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Laravel implementation of TenantContextInterface.
 * 
 * For now, uses a simple approach where tenant_id comes from
 * the authenticated user's tenant. In a more complex multi-tenant
 * system, this could be determined from subdomain, header, etc.
 */
final class TenantContext implements TenantContextInterface
{
    private ?string $currentTenantId = null;
    private ?string $currentUserId = null;

    /**
     * {@inheritdoc}
     */
    public function getCurrentTenantId(): ?string
    {
        // If manually set, return that
        if ($this->currentTenantId !== null) {
            return $this->currentTenantId;
        }

        // Try to get from authenticated user
        $user = Auth::user();
        if ($user !== null && method_exists($user, 'getTenantId')) {
            return $user->getTenantId();
        }

        // For now, return a default tenant for development
        // In production, this should throw or return null
        return 'default-tenant';
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentTenantId(?string $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUserId(): ?string
    {
        // If manually set, return that
        if ($this->currentUserId !== null) {
            return $this->currentUserId;
        }

        // Try to get from authenticated user
        $user = Auth::user();
        if ($user !== null) {
            return (string) $user->getAuthIdentifier();
        }

        return null;
    }

    /**
     * Set the current user ID.
     */
    public function setCurrentUserId(?string $userId): void
    {
        $this->currentUserId = $userId;
    }

    /**
     * Clear the current context.
     */
    public function clear(): void
    {
        $this->currentTenantId = null;
        $this->currentUserId = null;
    }

    /**
     * Execute a callback within a specific tenant context.
     */
    public function withTenant(string $tenantId, callable $callback): mixed
    {
        $previousTenantId = $this->currentTenantId;
        
        try {
            $this->currentTenantId = $tenantId;
            return $callback();
        } finally {
            $this->currentTenantId = $previousTenantId;
        }
    }
}
