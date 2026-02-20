<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

interface InventoryDataProviderInterface
{
    public function getTotalInventoryValue(string $tenantId, string $asOfDate): float;

    public function getAverageInventoryTurnover(string $tenantId): float;

    public function getDaysInventoryOutstanding(string $tenantId): float;

    public function getInventoryByCategory(string $tenantId): array;
}
