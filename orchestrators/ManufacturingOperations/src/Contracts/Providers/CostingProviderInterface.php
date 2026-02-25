<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers;

use Nexus\Orchestrators\ManufacturingOperations\DTOs\CostCalculationRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\CostCalculationResult;

interface CostingProviderInterface
{
    /**
     * Calculate the estimated cost for a production run.
     */
    public function calculateEstimatedCost(string $tenantId, CostCalculationRequest $request): CostCalculationResult;

    /**
     * Record actual costs incurred during production.
     */
    public function recordActualCost(string $tenantId, string $orderId, float $amount, string $currency): void;
}
