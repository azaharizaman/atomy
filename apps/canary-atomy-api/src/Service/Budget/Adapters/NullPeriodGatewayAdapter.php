<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\PeriodContextInterface;
use Nexus\Budget\Contracts\PeriodGatewayInterface;

final class NullPeriodGatewayAdapter implements PeriodGatewayInterface
{
    public function findById(string $periodId): ?PeriodContextInterface
    {
        return null;
    }

    public function getNextPeriod(string $periodId): ?PeriodContextInterface
    {
        return null;
    }
}
