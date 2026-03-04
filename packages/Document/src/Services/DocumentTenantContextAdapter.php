<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\TenantContextInterface;
use Nexus\Tenant\Contracts\TenantContextInterface as GlobalTenantContext;

/**
 * Adapter for Document package to use the Global Tenant Context.
 */
final readonly class DocumentTenantContextAdapter implements TenantContextInterface
{
    public function __construct(
        private GlobalTenantContext $tenantContext
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function requireTenant(): string
    {
        return $this->tenantContext->requireTenant();
    }
}
