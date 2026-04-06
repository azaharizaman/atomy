<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface GLAccountMappingRepositoryInterface
{
    /**
     * @return array<GLAccountMappingRuleViewInterface>
     */
    public function getMappingsForSubledger(string $tenantId, string $subledgerType): array;
}
