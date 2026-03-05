<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Port for procurement management operations.
 */
interface OrchestratorProcurementManagerInterface
{
    /**
     * Get requisition by RFQ ID.
     *
     * @param string $rfqId
     * @return OrchestratorRequisitionInterface|null
     */
    public function getRequisition(string $rfqId): ?OrchestratorRequisitionInterface;
}
