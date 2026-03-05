<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Minimal requisition line read-model needed by QuotationIntelligence.
 */
interface OrchestratorRequisitionLineInterface
{
    public function getUnit(): ?string;
}

