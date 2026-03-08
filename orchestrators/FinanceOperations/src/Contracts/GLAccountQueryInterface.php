<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Orchestrator-facing contract to query GL accounts.
 */
interface GLAccountQueryInterface
{
    /**
     * Find a GL account by account code.
     */
    public function find(string $tenantId, string $accountCode): ?object;
}
