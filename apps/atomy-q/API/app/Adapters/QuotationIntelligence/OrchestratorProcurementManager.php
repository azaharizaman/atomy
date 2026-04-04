<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Adapters\QuotationIntelligence\Concerns\WrapsModels;
use App\Models\Rfq;
use Nexus\QuotationIntelligence\Contracts\OrchestratorProcurementManagerInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorRequisitionInterface;

final class OrchestratorProcurementManager implements OrchestratorProcurementManagerInterface
{
    use WrapsModels;

    public function getRequisition(string $rfqId): ?OrchestratorRequisitionInterface
    {
        // For Alpha, we rely on ULID uniqueness, but in production, 
        // we'd want to enforce tenant isolation from the container/context.
        $rfq = Rfq::query()->with('lineItems')->find($rfqId);

        return $rfq ? $this->wrapRequisition($rfq) : null;
    }
}
