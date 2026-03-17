<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Http\Request;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Exceptions\TenantContextNotSetException;

final readonly class RequestTenantContext implements TenantContextInterface
{
    public function __construct(private Request $request)
    {
    }

    public function setTenant(string $tenantId): void
    {
        $this->request->attributes->set('auth_tenant_id', $tenantId);
    }

    public function getCurrentTenantId(): ?string
    {
        $tenantId = $this->request->attributes->get('auth_tenant_id');
        if (!is_string($tenantId) || $tenantId === '') {
            return null;
        }

        return $tenantId;
    }

    public function hasTenant(): bool
    {
        return $this->getCurrentTenantId() !== null;
    }

    public function getCurrentTenant(): ?TenantInterface
    {
        // TODO(phase-2): Resolve and return a full TenantInterface entity (e.g., via a tenant repository) when available.
        return null;
    }

    public function clearTenant(): void
    {
        $this->request->attributes->remove('auth_tenant_id');
    }

    public function requireTenant(): string
    {
        $tenantId = $this->getCurrentTenantId();
        if ($tenantId === null) {
            throw TenantContextNotSetException::required();
        }

        return $tenantId;
    }
}

