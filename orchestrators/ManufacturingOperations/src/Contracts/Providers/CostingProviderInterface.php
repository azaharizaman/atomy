<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Contracts\Providers;

use Nexus\ManufacturingOperations\DTOs\CostCalculationRequest;
use Nexus\ManufacturingOperations\DTOs\CostCalculationResult;
use Nexus\ManufacturingOperations\DTOs\CurrencyCode;

interface CostingProviderInterface
{
    /**
     * Calculate the estimated cost for a production run.
     */
    public function calculateEstimatedCost(string $tenantId, CostCalculationRequest $request): CostCalculationResult;

    /**
     * Record actual costs incurred during production.
     */
    public function recordActualCost(string $tenantId, string $orderId, string $amount, CurrencyCode $currency): void;

    /**
     * Get material costs for a specific order.
     * 
     * @return \Nexus\ManufacturingOperations\DTOs\MaterialCost[]
     */
    public function getMaterialCosts(string $tenantId, string $orderId): array;

    /**
     * Get labor costs for a specific order.
     */
    public function getLaborCosts(string $tenantId, string $orderId): string;

    /**
     * Get overhead costs for a specific order.
     */
    public function getOverheadCosts(string $tenantId, string $orderId): string;
}
