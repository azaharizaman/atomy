<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Contracts\Providers;

use Nexus\ManufacturingOperations\DTOs\InspectionRequest;
use Nexus\ManufacturingOperations\DTOs\InspectionResult;

interface QualityProviderInterface
{
    /**
     * Create a quality inspection requirement for a production order.
     * 
     * @return string inspectionId
     */
    public function createInspection(string $tenantId, InspectionRequest $request): string;

    /**
     * Record the result of a quality inspection.
     */
    public function recordInspectionResult(string $tenantId, InspectionResult $result): void;

    /**
     * Check if a production order has passed all required inspections.
     */
    public function checkCompliance(string $tenantId, string $orderId): bool;

    /**
     * Remove an inspection record (used for rollbacks).
     * 
     * This operation SHOULD be idempotent. If the inspection record does not exist, 
     * it should be treated as a no-op success. Transient errors should throw exceptions.
     */
    public function deleteInspection(string $tenantId, string $inspectionId): void;
}
