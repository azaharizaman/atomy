<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Services;

use Nexus\Identity\Contracts\AuthContextInterface;
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\ManufacturingOperations\Contracts\ManufacturingOrchestratorInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\BomProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\CostingProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\InventoryProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\ManufacturingProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\QualityProviderInterface;
use Nexus\ManufacturingOperations\DTOs\BomLookupRequest;
use Nexus\ManufacturingOperations\DTOs\CostCalculationRequest;
use Nexus\ManufacturingOperations\DTOs\InspectionRequest;
use Nexus\ManufacturingOperations\DTOs\ProductionOrder;
use Nexus\ManufacturingOperations\DTOs\ProductionOrderRequest;
use Nexus\ManufacturingOperations\DTOs\ProductionOrderStatus;
use Nexus\ManufacturingOperations\DTOs\StockReservationRequest;
use Nexus\ManufacturingOperations\Events\OrderCompleted;
use Nexus\ManufacturingOperations\Events\OrderPlanned;
use Nexus\ManufacturingOperations\Events\OrderReleased;
use Nexus\ManufacturingOperations\Exceptions\ManufacturingOperationsException;
use Nexus\ManufacturingOperations\Exceptions\StockShortageException;
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
        private AuthContextInterface $authContext,
        private PolicyEvaluatorInterface $policyEvaluator,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
    ) {}

    private function authorize(string $tenantId, string $action, mixed $resource): void
    {
        $user = $this->authContext->getCurrentUser();
        if (!$user) {
             throw new \RuntimeException("Unauthorized: No authenticated user context.");
        }

        if (!$this->policyEvaluator->evaluate($user, $action, $resource, ['tenant_id' => $tenantId])) {
            $this->logger->warning("Access denied", [
                'user_id' => $user->getId(),
                'action' => $action,
                'resource' => is_string($resource) ? $resource : get_class($resource),
                'tenant_id' => $tenantId,
            ]);
            throw new \RuntimeException("Access denied: You do not have permission to perform '{$action}'.");
        }
    }

    public function planProduction(string $tenantId, ProductionOrderRequest $request): ProductionOrder
    {
        $this->authorize($tenantId, 'manufacturing:plan', $request->productId);

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
            'currency' => $estimatedCost->currency->value,
        ]);

        // 3. Enrich Request with Plan Data
        $enrichedRequest = new ProductionOrderRequest(
            productId: $request->productId,
            quantity: $request->quantity,
            dueDate: $request->dueDate,
            priority: $request->priority,
            bomId: $bomResult->bomId,
            routingId: $request->routingId,
            estimatedMaterialCost: $estimatedCost->estimatedMaterialCost,
            estimatedLaborCost: $estimatedCost->estimatedLaborCost,
            estimatedOverheadCost: $estimatedCost->estimatedOverheadCost,
            currency: $estimatedCost->currency
        );

        $order = $this->manufacturingProvider->createOrder($tenantId, $enrichedRequest);

        $this->dispatcher->dispatch(new OrderPlanned($tenantId, $order));

        return $order;
    }

    public function releaseOrder(string $tenantId, string $orderId): ProductionOrder
    {
        $this->authorize($tenantId, 'manufacturing:release', $orderId);

        $this->logger->info('Releasing production order', ['order_id' => $orderId]);

        $order = $this->manufacturingProvider->getOrder($tenantId, $orderId);

        if ($order->status !== ProductionOrderStatus::Planned) {
            throw new ManufacturingOperationsException("Order {$orderId} cannot be released from status {$order->status->value}");
        }

        // 1. Get Requirements (Explode BOM)
        $bomRequest = new BomLookupRequest(
            productId: $order->productId,
            bomId: $order->bomId
        );
        $bomResult = $this->bomProvider->getBom($tenantId, $bomRequest);

        // Calculate required quantities
        $requirements = [];
        foreach ($bomResult->components as $componentId => $qtyPerUnit) {
            $requirements[$componentId] = $qtyPerUnit * $order->quantity;
        }

        // 2. Reserve Stock (Atomic)
        $reservationRequest = new StockReservationRequest(
            orderId: $orderId,
            items: $requirements
        );
        
        $reservationResult = $this->inventoryProvider->reserveStock($tenantId, $reservationRequest);
        
        if (!$reservationResult->success) {
            throw StockShortageException::forShortages($reservationResult->shortages);
        }

        $inspectionId = null;
        try {
            // 3. Initialize Quality Inspection
            $inspectionId = $this->qualityProvider->createInspection($tenantId, new InspectionRequest(
                orderId: $orderId,
                productId: $order->productId,
                type: \Nexus\ManufacturingOperations\DTOs\InspectionType::Final
            ));

            // 4. Update Status and fetch final state
            $this->manufacturingProvider->updateOrderStatus($tenantId, $orderId, ProductionOrderStatus::Released);
            $finalOrder = $this->manufacturingProvider->getOrder($tenantId, $orderId);

            $this->dispatcher->dispatch(new OrderReleased($tenantId, $finalOrder));

            return $finalOrder;

        } catch (\Exception $e) {
            $this->logger->error('Order release failed, compensating...', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            $this->compensateOrderRelease($tenantId, $reservationResult->reservationId, $inspectionId);
            throw $e;
        }
    }

    private function compensateOrderRelease(string $tenantId, string $reservationId, ?string $inspectionId): void
    {
        try {
            $this->inventoryProvider->releaseReservation($tenantId, $reservationId);
            if ($inspectionId) {
                $this->qualityProvider->deleteInspection($tenantId, $inspectionId);
            }
        } catch (\Exception $e) {
            $this->logger->critical('Release compensation failed!', [
                'tenant_id' => $tenantId,
                'reservation_id' => $reservationId,
                'inspection_id' => $inspectionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function completeOrder(string $tenantId, string $orderId): ProductionOrder
    {
        $this->authorize($tenantId, 'manufacturing:complete', $orderId);

        $this->logger->info('Completing production order', ['order_id' => $orderId]);

        $order = $this->manufacturingProvider->getOrder($tenantId, $orderId);

        if ($order->status !== ProductionOrderStatus::Released && $order->status !== ProductionOrderStatus::InProgress) {
             throw new ManufacturingOperationsException("Order {$orderId} cannot be completed from status {$order->status->value}");
        }

        // 1. Verify Quality Compliance
        if (!$this->qualityProvider->checkCompliance($tenantId, $orderId)) {
            throw new ManufacturingOperationsException("Order {$orderId} has pending or failed quality inspections");
        }

        // 2. Issue Stock (Consumption) - Fail fast if no reservation
        if (!$order->reservationId) {
            throw new ManufacturingOperationsException("Cannot complete order {$orderId}: Missing reservation ID for material consumption.");
        }

        $this->inventoryProvider->issueStock($tenantId, $order->reservationId, $order->quantity);

        // 3. Receive Finished Goods
        $this->inventoryProvider->receiveStock($tenantId, $order->productId, $order->quantity, 'WH-DEFAULT');
        
        // 4. Calculate and Record Actual Costs (Computation instead of hardcoded placeholder)
        $actualMaterialCosts = $this->costingProvider->getMaterialCosts($tenantId, $orderId);
        $totalActualMaterial = "0.0000";
        foreach ($actualMaterialCosts as $cost) {
            $totalActualMaterial = bcadd($totalActualMaterial, $cost->totalCost, 4);
        }

        $laborCost = $this->costingProvider->getLaborCosts($tenantId, $orderId);
        $overheadCost = $this->costingProvider->getOverheadCosts($tenantId, $orderId);
        
        $totalActual = bcadd(bcadd($totalActualMaterial, $laborCost, 4), $overheadCost, 4);

        $this->costingProvider->recordActualCost($tenantId, $orderId, $totalActual, \Nexus\ManufacturingOperations\DTOs\CurrencyCode::USD);

        // 5. Update Status
        $this->manufacturingProvider->updateOrderStatus($tenantId, $orderId, ProductionOrderStatus::Completed);

        $finalOrder = $this->manufacturingProvider->getOrder($tenantId, $orderId);
        
        $this->dispatcher->dispatch(new OrderCompleted($tenantId, $finalOrder));

        return $finalOrder;
    }
}
