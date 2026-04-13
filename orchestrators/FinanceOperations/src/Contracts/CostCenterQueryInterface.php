<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface CostCenterQueryInterface
{
    public function find(string $tenantId, string $costCenterId): ?CostCenterRuleViewInterface;
}
