<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract for period subledger closure checks.
 */
interface SubledgerPeriodStateInterface
{
    public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool;
}
