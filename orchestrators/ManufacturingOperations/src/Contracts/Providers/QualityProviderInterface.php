<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers;

use Nexus\Orchestrators\ManufacturingOperations\DTOs\InspectionRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\InspectionResult;

interface QualityProviderInterface
{
    /**
     * Create a quality inspection requirement for a production order.
     */
    public function createInspection(string $tenantId, InspectionRequest $request): void;

    /**
     * Record the result of a quality inspection.
     */
    public function recordInspectionResult(string $tenantId, string $inspectionId, InspectionResult $result): void;

    /**
     * Check if a production order has passed all required inspections.
     */
    public function checkCompliance(string $tenantId, string $orderId): bool;
}
