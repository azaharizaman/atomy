<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

interface ReceivableDataProviderInterface
{
    public function getTotalReceivables(string $tenantId, string $asOfDate): float;

    public function getAverageCollectionPeriod(string $tenantId): int;

    public function getAgingReceivables(string $tenantId): array;

    public function getDaysSalesOutstanding(string $tenantId): float;
}
