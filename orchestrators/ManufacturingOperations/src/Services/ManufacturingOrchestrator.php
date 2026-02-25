<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Services;

use Nexus\Orchestrators\ManufacturingOperations\Contracts\ManufacturingOrchestratorInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\BomProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\CostingProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\InventoryProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\ManufacturingProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\QualityProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\BomLookupRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\CostCalculationRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\InspectionRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrder;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrderRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\StockCheckRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\StockReservationRequest;
use Nexus\Orchestrators\ManufacturingOperations\Exceptions\ManufacturingOperationsException;
use Nexus\Orchestrators\ManufacturingOperations\Exceptions\StockShortageException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final readonly class ManufacturingOrchestrator implements ManufacturingOrchestratorInterface
{
    public function __construct(
        private ManufacturingProviderInterface $manufacturingProvider,
        private BomProviderInterface $bomProvider,
        private InventoryProviderInterface $inventoryProvider,
        private QualityProviderInterface $qualityProvider,
        private CostingProviderInterface $costingProvider,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function planProduction(string $tenantId, ProductionOrderRequest $request): ProductionOrder
    {
        $this->logger->info('Planning production order', [
            'tenant_id' => $tenantId,
            'product_id' => $request->productId,
            'quantity' => $request->quantity,
        ]);

        // 1. Validate BOM
        $bomRequest = new BomLookupRequest(
            productId: $request->productId,
            bomId: $request->bomId
        );
        
        try {
            $bomResult = $this->bomProvider->getBom($tenantId, $bomRequest);
        } catch (\Exception $e) {
            $this->logger->error('BOM validation failed', ['error' => $e->getMessage()]);
            throw new ManufacturingOperationsException("Valid BOM not found for product {$request->productId}", 0, $e);
        }

        // 2. Estimate Costs
        $costRequest = new CostCalculationRequest(
            productId: $request->productId,
            quantity: $request->quantity,
            bomId: $bomResult->bomId,
            routingId: $request->routingId
        );
        
        $estimatedCost = $this->costingProvider->calculateEstimatedCost($tenantId, $costRequest);
        
        $this->logger->info('Estimated production cost calculated', [
            'total' => $estimatedCost->getTotal(),
            'currency' => $estimatedCost->currency,
        ]);

        // 3. Create Order (Planned)
        return $this->manufacturingProvider->createOrder($tenantId, $request);
    }

    public function releaseOrder(string $tenantId, string $orderId): ProductionOrder
    {
        $this->logger->info('Releasing production order', ['order_id' => $orderId]);

        $order = $this->manufacturingProvider->getOrder($tenantId, $orderId);

        if ($order->status !== 'Planned') {
            throw new ManufacturingOperationsException("Order {$orderId} cannot be released from status {$order->status}");
        }

        // 1. Get Requirements (Explode BOM)
        $bomRequest = new BomLookupRequest(productId: $order->productId); // Should rely on order's BOM ID if available
        $bomResult = $this->bomProvider->getBom($tenantId, $bomRequest);

        // Calculate required quantities
        $requirements = [];
        foreach ($bomResult->components as $componentId => $qtyPerUnit) {
            $requirements[$componentId] = $qtyPerUnit * $order->quantity;
        }

        // 2. Check Stock
        $stockCheck = new StockCheckRequest($requirements);
        if (!$this->inventoryProvider->checkStockAvailability($tenantId, $stockCheck)) {
            throw StockShortageException::forShortages('Insufficient stock for production release');
        }

        // 3. Reserve Stock
        $reservationRequest = new StockReservationRequest(
            orderId: $orderId,
            items: $requirements
        );
        
        $reservationResult = $this->inventoryProvider->reserveStock($tenantId, $reservationRequest);
        
        if (!$reservationResult->success) {
            throw StockShortageException::forShortages($reservationResult->shortages);
        }

        // 4. Initialize Quality Inspection (if required)
        try {
            $this->qualityProvider->createInspection($tenantId, new InspectionRequest(
                orderId: $orderId,
                productId: $order->productId,
                type: 'Final' // Default to final inspection
            ));
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize quality inspection', ['error' => $e->getMessage()]);
            // Rollback reservation
            $this->inventoryProvider->releaseReservation($tenantId, $reservationResult->reservationId);
            throw $e;
        }

        // 5. Update Status
        $this->manufacturingProvider->updateOrderStatus($tenantId, $orderId, 'Released');

        return $this->manufacturingProvider->getOrder($tenantId, $orderId);
    }

    public function completeOrder(string $tenantId, string $orderId): ProductionOrder
    {
        $this->logger->info('Completing production order', ['order_id' => $orderId]);

        $order = $this->manufacturingProvider->getOrder($tenantId, $orderId);

        if ($order->status !== 'Released' && $order->status !== 'InProgress') {
             throw new ManufacturingOperationsException("Order {$orderId} cannot be completed from status {$order->status}");
        }

        // 1. Verify Quality Compliance
        if (!$this->qualityProvider->checkCompliance($tenantId, $orderId)) {
            throw new ManufacturingOperationsException("Order {$orderId} has pending or failed quality inspections");
        }

        // 2. Consume Materials (Issue Stock)
        if ($order->reservationId) {
            // Assume issueStock handles consuming the reservation for the order quantity
            $this->inventoryProvider->issueStock($tenantId, $order->reservationId, $order->quantity);
        } else {
            $this->logger->warning("Order {$orderId} completed without reservation ID.");
        }

        // 3. Receive Finished Goods
        // Assuming default warehouse for now, or provider logic
        $this->inventoryProvider->receiveStock($tenantId, $order->productId, $order->quantity, 'WH-DEFAULT');
        
        // 4. Calculate Final Costs
        $this->costingProvider->recordActualCost($tenantId, $orderId, 0.0, 'USD'); // Placeholder

        // 5. Update Status
        $this->manufacturingProvider->updateOrderStatus($tenantId, $orderId, 'Completed');

        return $this->manufacturingProvider->getOrder($tenantId, $orderId);
    }
}
