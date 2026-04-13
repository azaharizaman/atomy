<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface GLAccountMappingRuleViewInterface
{
    public function getTransactionType(): string;

    public function getGLAccountCode(): string;
}
