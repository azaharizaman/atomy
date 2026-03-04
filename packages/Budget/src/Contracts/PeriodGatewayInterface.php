<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PeriodGatewayInterface
{
    public function findById(string $periodId): ?PeriodContextInterface;

    public function getNextPeriod(string $periodId): ?PeriodContextInterface;
}
