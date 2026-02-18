<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use DateTimeImmutable;
use Nexus\SupplyChainOperations\Contracts\SupplyChainStockManagerInterface;
use Nexus\SupplyChainOperations\Contracts\AtpCalculationServiceInterface;
use Nexus\SupplyChainOperations\Contracts\AuditLoggerInterface;
use Nexus\SupplyChainOperations\ValueObjects\AvailableToPromiseResult;
use Psr\Log\LoggerInterface;

final readonly class DynamicLeadTimeCoordinator
{
    public function __construct(
        private AtpCalculationServiceInterface $atpCalculationService,
        private SupplyChainStockManagerInterface $stockManager,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    public function calculateAtpDate(
        string $productId,
        float $requestedQuantity,
        string $warehouseId,
        ?string $preferredVendorId = null
    ): AvailableToPromiseResult {
        $now = new DateTimeImmutable();

        $this->logger->debug("Calculating ATP for product {$productId}, qty {$requestedQuantity}");

        $availableStock = $this->stockManager->getCurrentStock($productId, $warehouseId);

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

            $this->auditLogger->log(
                logName: 'supply_chain_atp_calculated',
                description: "ATP calculated for {$productId}: {$leadTimeData['totalDays']} days"
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
