<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Minimal tenant read-model needed by QuotationIntelligence.
 */
interface OrchestratorTenantInterface
{
    public function getCurrency(): string;
}

