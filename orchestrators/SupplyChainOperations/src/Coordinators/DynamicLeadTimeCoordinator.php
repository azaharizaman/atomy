<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use DateTimeImmutable;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\SupplyChainOperations\Services\AtpCalculationService;
use Nexus\SupplyChainOperations\ValueObjects\AvailableToPromiseResult;
use Psr\Log\LoggerInterface;

/**
 * Coordinator for Available-to-Promise (ATP) calculations.
 *
 * Orchestrates ATP workflow by:
 * 1. Checking stock availability
 * 2. Delegating lead time calculations to AtpCalculationService
 * 3. Building ATP result with confidence scores
 *
 * Following the Advanced Orchestrator Pattern, this coordinator
 * delegates heavy calculations to Services while handling flow orchestration.
 *
 * @see \Nexus\SupplyChainOperations\Services\AtpCalculationService
 * @see \Nexus\SupplyChainOperations\ValueObjects\AvailableToPromiseResult
 */
final readonly class DynamicLeadTimeCoordinator
{
    public function __construct(
        private AtpCalculationService $atpCalculationService,
        private StockManagerInterface $stockManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Calculate Available-to-Promise date for a product and quantity.
     *
     * @param string $productId Product variant identifier
     * @param float $requestedQuantity Quantity customer wants
     * @param string $warehouseId Preferred warehouse
     * @param string|null $preferredVendorId Optional preferred vendor
     * @return AvailableToPromiseResult
     */
    public function calculateAtpDate(
        string $productId,
        float $requestedQuantity,
        string $warehouseId,
        ?string $preferredVendorId = null
    ): AvailableToPromiseResult {
        $now = new DateTimeImmutable();

        $this->logger->debug("Calculating ATP for product {$productId}, qty {$requestedQuantity}");

        $availableStock = $this->stockManager->getAvailableStock($productId, $warehouseId);

        if ($availableStock >= $requestedQuantity) {
            $this->logger->info("Product {$productId} available now: {$availableStock} >= {$requestedQuantity}");
            return AvailableToPromiseResult::availableNow($now);
        }

        $shortageQty = $requestedQuantity - $availableStock;

        try {
            $leadTimeData = $this->atpCalculationService->calculateLeadTimeData(
                $productId,
                $preferredVendorId
            );

            $promisedDate = $now->modify("+{$leadTimeData['totalDays']} days");

            $confidence = $this->atpCalculationService->calculateConfidence(
                $leadTimeData['vendorAccuracy'],
                $leadTimeData['variance'],
                $leadTimeData['baseDays']
            );

            $this->logger->info(
                "ATP calculated for {$productId}: {$leadTimeData['totalDays']} days, " .
                "confidence {$confidence}, shortage {$shortageQty}"
            );

            return new AvailableToPromiseResult(
                promisedDate: $promisedDate,
                confidence: $confidence,
                availableNow: false,
                requiresProcurement: true,
                estimatedLeadTimeDays: $leadTimeData['totalDays'],
                shortageQuantity: $shortageQty,
                metadata: [
                    'base_lead_time_days' => $leadTimeData['baseDays'],
                    'variance_buffer_days' => $leadTimeData['varianceBuffer'],
                    'reliability_buffer_days' => $leadTimeData['reliabilityBuffer'],
                    'seasonal_buffer_days' => $leadTimeData['seasonalBuffer'],
                    'vendor_id' => $leadTimeData['vendorId'],
                    'vendor_accuracy' => $leadTimeData['vendorAccuracy'],
                    'lead_time_variance' => $leadTimeData['variance'],
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                "Failed to calculate ATP for {$productId}: " . $e->getMessage(),
                ['exception' => $e]
            );

            return new AvailableToPromiseResult(
                promisedDate: $now->modify('+30 days'),
                confidence: 0.5,
                availableNow: false,
                requiresProcurement: true,
                estimatedLeadTimeDays: 30,
                shortageQuantity: $shortageQty,
                metadata: ['error' => $e->getMessage(), 'fallback' => true]
            );
        }
    }

    /**
     * Get delivery estimates for multiple products.
     *
     * @param array<int, array{product_id: string, quantity: float, warehouse_id: string, vendor_id?: string}> $items
     * @return array<string, AvailableToPromiseResult>
     */
    public function calculateAtpForMultiple(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $results[$productId] = $this->calculateAtpDate(
                $productId,
                $item['quantity'],
                $item['warehouse_id'],
                $item['vendor_id'] ?? null
            );
        }

        return $results;
    }
}
