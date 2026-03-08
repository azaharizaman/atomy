<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Orchestrator-facing contract to check subledger closure status.
 */
interface SubledgerClosureQueryInterface
{
    /**
     * Determine whether a subledger is closed for a period.
     */
    public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool;
}
