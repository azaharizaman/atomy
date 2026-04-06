<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Adapters\QuotationIntelligence\Concerns\WrapsModels;
use App\Models\Rfq;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorProcurementManagerInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorRequisitionInterface;

final class OrchestratorProcurementManager implements OrchestratorProcurementManagerInterface
{
    use WrapsModels;

    public function __construct(
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function getRequisition(string $rfqId): ?OrchestratorRequisitionInterface
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        $query = Rfq::query()
            ->with(['lineItems' => static fn ($q) => $q->orderBy('sort_order')]);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $rfq = $query->find($rfqId);

        if ($rfq !== null) {
            // Extra guard: if line items ever get cross-tenant-corrupted, fail closed.
            foreach ($rfq->lineItems as $lineItem) {
                if ((string) $lineItem->tenant_id !== (string) $rfq->tenant_id) {
                    return null;
                }
            }
        }

        return $rfq ? $this->wrapRequisition($rfq) : null;
    }
}
