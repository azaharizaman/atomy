<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract used by GL account mapping rule checks.
 */
interface GLAccountQueryInterface
{
    /**
     * @return object|null GL account aggregate or null when not found
     */
    public function find(string $tenantId, string $accountCode): ?object;
}
