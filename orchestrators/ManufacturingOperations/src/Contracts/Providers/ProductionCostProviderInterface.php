<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers;

use Nexus\Orchestrators\ManufacturingOperations\DataTransferObjects\MaterialCost;

interface ProductionCostProviderInterface
{
    /**
     * @param string $tenantId
     * @param string $orderId
     * @return MaterialCost[]
     */
    public function getMaterialCosts(string $tenantId, string $orderId): array;

    public function getLaborCosts(string $tenantId, string $orderId): float;

    public function getOverheadCosts(string $tenantId, string $orderId): float;
}
