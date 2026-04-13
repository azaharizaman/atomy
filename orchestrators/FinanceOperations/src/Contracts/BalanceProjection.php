<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface BalanceProjection
{
    public function getBalance(): string;
    public function getCurrency(): string;
    public function getPeriodId(): string;
}
