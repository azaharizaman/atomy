<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface GLAccountQueryInterface
{
    public function find(string $tenantId, string $accountCode): ?GLAccountRuleViewInterface;
}
