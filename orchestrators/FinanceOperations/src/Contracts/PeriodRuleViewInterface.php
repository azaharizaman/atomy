<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface PeriodRuleViewInterface
{
    public function isOpen(): bool;

    public function getStatus(): string;
}
