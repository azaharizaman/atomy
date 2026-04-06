<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface GLAccountRuleViewInterface
{
    public function isActive(): bool;
}
