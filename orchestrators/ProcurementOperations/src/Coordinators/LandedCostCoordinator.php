<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\ProcurementOperations\Contracts\LandedCostCoordinatorInterface;
use Nexus\ProcurementOperations\DataProviders\GoodsReceiptContextProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for landed cost capitalization.
 */
final readonly class LandedCostCoordinator implements LandedCostCoordinatorInterface
{
    public function __construct(
        private GoodsReceiptContextProvider $contextProvider,
        private StockManagerInterface $stockManager,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @inheritDoc
     */
    public function capitalizeCosts(
        string $tenantId,
        string $grnId,
        array $costs,
        string $allocatedBy
    ): void {
        $context = $this->contextProvider->getContext($tenantId, $grnId);
        $totalAdditionalCost = array_sum($costs);
        
        // Use total value for allocation (Pro-rata by value)
        $totalValue = (float) $context->totalValueCents;

        $this->logger->info('Starting landed cost capitalization', [
            'tenant_id' => $tenantId,
            'grn_id' => $grnId,
            'total_value' => $totalValue,
            'additional_cost' => $totalAdditionalCost
        ]);

        if ($totalValue <= 0) {
            // Fallback to pro-rata by quantity if value is zero
            $this->allocateByQuantity($context->lineItems, $totalAdditionalCost);
            return;
        }

        foreach ($context->lineItems as $line) {
            $lineValue = (float) ($line['quantityReceived'] * $line['unitPriceCents']);
            $allocatedCost = ($lineValue / $totalValue) * $totalAdditionalCost;

            $this->stockManager->capitalizeLandedCost($line['productId'], $allocatedCost);
        }
    }

    private function allocateByQuantity(array $lineItems, float $totalCost): void
    {
        $totalQty = array_sum(array_column($lineItems, 'quantityReceived'));
        if ($totalQty <= 0) return;

        foreach ($lineItems as $line) {
            $allocatedCost = ($line['quantityReceived'] / $totalQty) * $totalCost;
            $this->stockManager->capitalizeLandedCost($line['productId'], $allocatedCost);
        }
    }
}
