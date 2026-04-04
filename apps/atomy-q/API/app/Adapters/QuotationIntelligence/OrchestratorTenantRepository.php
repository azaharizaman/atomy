<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Adapters\QuotationIntelligence\Concerns\WrapsModels;
use App\Models\Tenant;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantRepositoryInterface;

final class OrchestratorTenantRepository implements OrchestratorTenantRepositoryInterface
{
    use WrapsModels;

    public function findById(string $id): ?OrchestratorTenantInterface
    {
        $tenant = Tenant::query()->find($id);

        return $tenant ? $this->wrapTenant($tenant) : null;
    }
}
