<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\AuditLogger\Services\AuditLogManager;
use Psr\Log\LoggerInterface;

/**
 * Coordinates Landed Cost capitalization.
 *
 * Distributes freight, insurance, and duties across received inventory items.
 */
final readonly class LandedCostCoordinator
{
    public function __construct(
        private StockManagerInterface $stockManager,
        private GoodsReceiptRepositoryInterface $grnRepository,
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Capitalize a cost amount onto a specific Goods Receipt Note (GRN).
     *
     * @param string $grnId The GRN reference
     * @param float $amount The total landed cost to distribute (freight, duty, etc.)
     * @param string $allocationBasis 'value' or 'quantity' (Default: 'value')
     */
    public function distributeLandedCost(string $grnId, float $amount, string $allocationBasis = 'value'): void
    {
        $grn = $this->grnRepository->findById($grnId);
        
        if (!$grn) {
            $this->logger->error("LandedCostCoordinator: GRN {$grnId} not found.");
            return;
        }

        $lines = $grn->getLines();
        $lineBases = [];
        $totalBasis = 0.0;

        // 1. Calculate Basis Total
        foreach ($lines as $line) {
            $basis = 0.0;
            
            if ($allocationBasis === 'quantity') {
                $basis = $line->getQuantity();
            } else {
                // Default: Value Basis
                // Need to fetch PO Line Price
                $poLineRef = $line->getPoLineReference();
                $poLine = $this->purchaseOrderRepository->findLineByReference($poLineRef);
                
                if ($poLine) {
                    // Start with line total value (qty * price)
                    // If partial shipment, use GRN quantity * PO Unit Price
                    $unitPrice = $poLine->getUnitPrice();
                    $basis = $line->getQuantity() * $unitPrice;
                } else {
                    $this->logger->warning("LandedCostCoordinator: Could not find PO Line for Reference {$poLineRef}. Skipping line for valuation.");
                }
            }

            $lineBases[] = [
                'line' => $line,
                'basis' => $basis,
                'poLineRef' => $line->getPoLineReference(), // Keep reference
                'productId' => null // Will be resolved if needed
            ];
            $totalBasis += $basis;
        }

        if ($totalBasis <= 0) {
            $this->logger->warning("LandedCostCoordinator: GRN {$grnId} has zero basis ({$allocationBasis}). Cannot distribute cost.");
            return;
        }

        // 2. Distribute and Capitalize
        foreach ($lineBases as $entry) {
            $line = $entry['line'];
            $basis = $entry['basis'];
            
            if ($basis <= 0) {
                continue;
            }

            $share = $basis / $totalBasis;
            $allocatedAmount = $amount * $share;
            
            // Resolve Product ID
            // Ideally from PO Line, or maybe GRN Line has it?
            // GRN Line doesn't exposing ProductId in interface (checked earlier).
            // So we must get it from PO Line.
            
            $poLine = $this->purchaseOrderRepository->findLineByReference($entry['poLineRef']);
            $productId = $poLine?->getProductVariantId();

            if ($productId && $allocatedAmount > 0) {
                $this->stockManager->capitalizeLandedCost($productId, $allocatedAmount);

                $this->auditLogger->log(
                    logName: 'supply_chain_landed_cost_capitalized',
                    message: "Capitalized {$allocatedAmount} onto Product {$productId} from GRN {$grnId}",
                    context: [
                        'grn_id' => $grnId,
                        'product_id' => $productId,
                        'amount' => $allocatedAmount,
                        'basis' => $allocationBasis,
                        'source_total' => $amount
                    ]
                );
            } elseif (!$productId) {
                 $this->logger->warning("LandedCostCoordinator: No product ID found for line ref {$entry['poLineRef']}");
            }
        }
    }
}
