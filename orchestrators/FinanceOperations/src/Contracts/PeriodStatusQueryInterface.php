<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\Contracts\PeriodRuleViewInterface;

interface PeriodStatusQueryInterface
{
    public function getPeriod(string $tenantId, string $periodId): ?PeriodRuleViewInterface;

    public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool;
}
