<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for consignment inventory consumption and self-billing.
 */
final readonly class ConsignmentConsumptionCoordinator
{
    public function __construct(
        private PayableManagerInterface $payableManager,
        private StockManagerInterface $stockManager,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Process consumption of consignment stock and trigger self-billing.
     *
     * @param string $tenantId
     * @param string $vendorId
     * @param array<int, array{productId: string, quantity: float, unitPrice: float}> $items
     * @return string Generated bill ID
     */
    public function recordConsumption(string $tenantId, string $vendorId, array $items): string
    {
        $this->logger->info('Recording consignment consumption', [
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'item_count' => count($items)
        ]);

        // In a full implementation, this would involve complex stock ownership transfer logic.
        // For the orchestrator, we primarily bridge the consumption to the self-billing process.
        
        return $this->payableManager->recordConsignmentConsumption($vendorId, $items);
    }
}
