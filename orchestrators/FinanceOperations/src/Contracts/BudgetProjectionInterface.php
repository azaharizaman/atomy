<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface BudgetProjectionInterface
{
    public function getAmount(): string;
    public function getCurrency(): string;
    public function getPeriodId(): string;
    public function getBudgetVersionId(): ?string;
}
