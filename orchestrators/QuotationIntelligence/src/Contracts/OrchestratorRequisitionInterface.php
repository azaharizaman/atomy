<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Minimal requisition read-model needed by QuotationIntelligence.
 */
interface OrchestratorRequisitionInterface
{
    /**
     * @return array<OrchestratorRequisitionLineInterface>
     */
    public function getLines(): array;
}

