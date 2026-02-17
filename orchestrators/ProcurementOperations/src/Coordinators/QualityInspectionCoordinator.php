<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\QualityControl\Contracts\InspectionManagerInterface;
use Nexus\QualityControl\Enums\InspectionDecision;
use Nexus\ProcurementOperations\Contracts\QualityInspectionCoordinatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for quality inspection workflows.
 */
final readonly class QualityInspectionCoordinator implements QualityInspectionCoordinatorInterface
{
    public function __construct(
        private StockManagerInterface $stockManager,
        private InspectionManagerInterface $inspectionManager,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @inheritDoc
     */
    public function initiateInspection(
        string $tenantId,
        string $grnId,
        string $productId,
        string $warehouseId,
        float $quantity,
        ?string $lotId = null
    ): string {
        $this->logger->info('Initiating quality inspection for received goods', [
            'tenant_id' => $tenantId,
            'grn_id' => $grnId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity
        ]);

        // 1. Create the inspection lot in QualityControl package
        $inspection = $this->inspectionManager->createInspection(
            $productId,
            $quantity,
            $lotId,
            [
                'tenant_id' => $tenantId,
                'grn_id' => $grnId,
                'warehouse_id' => $warehouseId
            ]
        );

        // 2. Quarantine the stock in Inventory package
        $this->stockManager->quarantineStock($productId, $warehouseId, $quantity);

        return $inspection->getId();
    }

    /**
     * @inheritDoc
     */
    public function recordInspectionResult(
        string $tenantId,
        string $inspectionId,
        string $decision,
        string $inspectorId,
        string $notes = ''
    ): void {
        $inspection = $this->inspectionManager->getInspection($inspectionId);
        
        if ($inspection === null) {
            throw new \RuntimeException("Inspection lot not found: {$inspectionId}");
        }

        $this->logger->info('Processing inspection result', [
            'tenant_id' => $tenantId,
            'inspection_id' => $inspectionId,
            'decision' => $decision
        ]);

        // 1. Finalize the inspection record
        $this->inspectionManager->finalizeInspection($inspectionId, $decision, $inspectorId, $notes);

        // 2. Update inventory status based on decision
        $metadata = $inspection instanceof \Nexus\QualityControl\Contracts\InspectionInterface 
            ? $this->getInspectionMetadata($inspection) 
            : [];
        $warehouseId = $metadata['warehouse_id'] ?? 'default';

        if ($decision === InspectionDecision::ACCEPT->value || $decision === InspectionDecision::CONDITIONAL_ACCEPT->value) {
            $this->stockManager->releaseFromQuarantine($inspection->getProductId(), $warehouseId, $inspection->getQuantity());
        } else {
            // Material remains in quarantine for disposition (RTV or Scrapping)
            $this->logger->warning('Stock remains in quarantine due to inspection failure', [
                'inspection_id' => $inspectionId,
                'decision' => $decision
            ]);
        }
    }

    /**
     * Helper to extract metadata from inspection lot (implementation dependent)
     */
    private function getInspectionMetadata(object $inspection): array
    {
        // In a real implementation, this would be part of the InspectionInterface 
        // or fetched via a repository.
        return [];
    }
}
