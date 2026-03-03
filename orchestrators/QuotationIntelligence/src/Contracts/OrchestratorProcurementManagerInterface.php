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
     * @return object|null
     */
    public function getRequisition(string $rfqId): ?object;
}
