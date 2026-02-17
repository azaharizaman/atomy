<?php

declare(strict_types=1);

namespace Nexus\QualityControl\Contracts;

/**
 * Interface for quality inspection management
 */
interface InspectionManagerInterface
{
    /**
     * Create a new inspection lot
     */
    public function createInspection(
        string $productId, 
        float $quantity, 
        ?string $batchId = null,
        array $metadata = []
    ): InspectionInterface;

    /**
     * Record results for an inspection lot
     */
    public function recordResults(string $inspectionId, array $testResults): void;

    /**
     * Finalize inspection with a decision
     */
    public function finalizeInspection(
        string $inspectionId, 
        string $decision, 
        string $inspectorId,
        string $notes = ''
    ): void;

    /**
     * Get inspection details
     */
    public function getInspection(string $inspectionId): ?InspectionInterface;
}
