<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Orchestrator-facing contract to read fiscal periods.
 */
interface PeriodLookupInterface
{
    /**
     * Retrieve a period for a tenant by period ID.
     */
    public function getPeriod(string $tenantId, string $periodId): ?object;
}
