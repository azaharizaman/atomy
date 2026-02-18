<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\SupplyChainOperations\Contracts\SupplyChainStockManagerInterface;
use Nexus\SupplyChainOperations\Contracts\GoodsReceiptProviderInterface;
use Nexus\SupplyChainOperations\Contracts\PurchaseOrderLineProviderInterface;
use Nexus\SupplyChainOperations\Contracts\AuditLoggerInterface;
use Nexus\SupplyChainOperations\Contracts\LandedCostCoordinatorInterface;
use Psr\Log\LoggerInterface;

final readonly class LandedCostCoordinator implements LandedCostCoordinatorInterface
{
    public function __construct(
        private SupplyChainStockManagerInterface $stockManager,
        private GoodsReceiptProviderInterface $grnRepository,
        private PurchaseOrderLineProviderInterface $purchaseOrderRepository,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {
    }

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

        foreach ($lines as $line) {
            $basis = 0.0;
            
            if ($allocationBasis === 'quantity') {
                $basis = $line->getQuantity();
            } else {
                $poLineRef = $line->getPoLineReference();
                $poLine = $this->purchaseOrderRepository->findLineByReference($poLineRef);
                
                if ($poLine) {
                    $unitPrice = $poLine->getUnitPrice();
                    $basis = $line->getQuantity() * $unitPrice;
                } else {
                    $this->logger->warning("LandedCostCoordinator: Could not find PO Line for Reference {$poLineRef}. Skipping line for valuation.");
                }
            }

            $lineBases[] = [
                'line' => $line,
                'basis' => $basis,
                'poLineRef' => $line->getPoLineReference(),
            ];
            $totalBasis += $basis;
        }

        if ($totalBasis <= 0) {
            $this->logger->warning("LandedCostCoordinator: GRN {$grnId} has zero basis ({$allocationBasis}). Cannot distribute cost.");
            return;
        }

        foreach ($lineBases as $entry) {
            $line = $entry['line'];
            $basis = $entry['basis'];
            
            if ($basis <= 0) {
                continue;
            }

            $share = $basis / $totalBasis;
            $allocatedAmount = $amount * $share;
            
            $poLine = $this->purchaseOrderRepository->findLineByReference($entry['poLineRef']);
            $productId = $poLine?->getProductVariantId();

            if ($productId && $allocatedAmount > 0) {
                $this->stockManager->capitalizeLandedCost($productId, $allocatedAmount);

                $this->auditLogger->log(
                    logName: 'supply_chain_landed_cost_capitalized',
                    description: "Capitalized {$allocatedAmount} onto Product {$productId} from GRN {$grnId}"
                );
            } elseif (!$productId) {
                 $this->logger->warning("LandedCostCoordinator: No product ID found for line ref {$entry['poLineRef']}");
            }
        }
    }
}
