<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract used by period-open and subledger-close rule checks.
 */
interface PeriodQueryInterface
{
    /**
     * @return object|null Period aggregate or null when not found
     */
    public function getPeriod(string $tenantId, string $periodId): ?object;
}
