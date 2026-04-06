<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Adapters\QuotationIntelligence\Concerns\WrapsModels;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantRepositoryInterface;

final class OrchestratorTenantRepository implements OrchestratorTenantRepositoryInterface
{
    use WrapsModels;

    public function __construct(
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function findById(string $id): ?OrchestratorTenantInterface
    {
        $currentTenantId = $this->tenantContext->getCurrentTenantId();
        if ($currentTenantId !== null && $currentTenantId !== $id) {
            return null;
        }

        if (!Schema::hasTable('tenants')) {
            return $this->wrapContextTenant();
        }

        $tenant = Tenant::query()->find($id);

        return $tenant ? $this->wrapTenant($tenant) : $this->wrapContextTenant();
    }

    private function wrapContextTenant(): OrchestratorTenantInterface
    {
        return new class implements OrchestratorTenantInterface {
            public function getCurrency(): string
            {
                return 'USD';
            }
        };
    }
}
