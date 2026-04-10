<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\Contracts\GLAccountMappingRuleViewInterface;

interface GLAccountMappingQueryInterface
{
    /**
     * @return array<GLAccountMappingRuleViewInterface>
     */
    public function getMappingsForSubledger(string $tenantId, string $subledgerType): array;
}