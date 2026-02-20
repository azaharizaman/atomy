<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

interface PayableDataProviderInterface
{
    public function getTotalPayables(string $tenantId, string $asOfDate): float;

    public function getAveragePaymentPeriod(string $tenantId): int;

    public function getAgingPayables(string $tenantId): array;

    public function getDaysPayableOutstanding(string $tenantId): float;
}
