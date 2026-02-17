<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Interface for quality inspection coordination
 */
interface QualityInspectionCoordinatorInterface
{
    /**
     * Initiate inspection for a received goods lot
     */
    public function initiateInspection(
        string $tenantId,
        string $grnId,
        string $productId,
        string $warehouseId,
        float $quantity,
        ?string $lotId = null
    ): string;

    /**
     * Record inspection results and update stock status
     */
    public function recordInspectionResult(
        string $tenantId,
        string $inspectionId,
        string $decision,
        string $inspectorId,
        string $notes = ''
    ): void;
}
