<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Interface for querying GL account information.
 * 
 * @since 1.0.0
 */
interface GLAccountQueryInterface
{
    /**
     * Find a GL account by tenant ID and account code.
     *
     * @param string $tenantId The tenant identifier
     * @param string $accountCode The account code to find
     * @return GLAccountRuleViewInterface|null The account view if found, null otherwise
     */
    public function find(string $tenantId, string $accountCode): ?GLAccountRuleViewInterface;
}
