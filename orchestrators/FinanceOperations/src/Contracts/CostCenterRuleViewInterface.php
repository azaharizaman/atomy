<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface CostCenterRuleViewInterface
{
    public function isActive(): bool;

    public function canReceiveAllocations(): bool;

    public function getName(): string;
}
