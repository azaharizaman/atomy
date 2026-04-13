<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface BudgetRuleViewInterface
{
    public function isActive(): bool;
}
