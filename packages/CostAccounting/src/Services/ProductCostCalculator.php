<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Services;

use Nexus\CostAccounting\Contracts\Integration\InventoryDataProviderInterface;
use Nexus\CostAccounting\Contracts\Integration\ManufacturingDataProviderInterface;
use Nexus\CostAccounting\Contracts\ProductCostCalculatorInterface;
use Nexus\CostAccounting\Contracts\ProductCostPersistInterface;
use Nexus\CostAccounting\Contracts\ProductCostQueryInterface;
use Nexus\CostAccounting\Entities\ProductCost;
use Nexus\CostAccounting\Events\ProductCostCalculatedEvent;
use Nexus\CostAccounting\ValueObjects\ProductCostSnapshot;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Product Cost Calculator Service
 * 
 * Handles product cost calculations including material, labor,
 * and overhead costs with multi-level rollup support.
 */
final readonly class ProductCostCalculator implements ProductCostCalculatorInterface
{
    public function __construct(
        private ProductCostQueryInterface $productCostQuery,
        private ProductCostPersistInterface $productCostPersist,
        private InventoryDataProviderInterface $inventoryProvider,
        private ManufacturingDataProviderInterface $manufacturingProvider,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function calculate(
        string $productId,
        string $periodId,
        string $costType = 'standard'
    ): ProductCost {
        $this->logger->info('Calculating product cost', [
            'product_id' => $productId,
            'period_id' => $periodId,
            'cost_type' => $costType,
        ]);

        if ($costType === 'standard') {
            return $this->calculateStandardCost($productId, $periodId);
        }

        return $this->calculateActualCost($productId, $periodId);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateStandardCost(string $productId, string $periodId): ProductCost
    {
        // Get material cost from inventory
        $materialCost = $this->getMaterialCost($productId, $periodId);

        // Get labor cost from manufacturing
        $laborCost = $this->getLaborCost($productId, $periodId);

        // Calculate overhead cost
        $overheadCost = $this->calculateOverheadCost($productId, $periodId, $materialCost, $laborCost);

        // Create or update product cost
        $productCost = $this->productCostQuery->findByProduct($productId, $periodId);

        if ($productCost === null) {
            $productCost = new ProductCost(
                id: $this->generateId(),
                productId: $productId,
                costCenterId: $this->getDefaultCostCenter($productId),
                periodId: $periodId,
                tenantId: $this->getTenantId($productId),
                costType: 'standard',
                currency: 'USD',
                materialCost: $materialCost,
                laborCost: $laborCost,
                overheadCost: $overheadCost
            );
            $this->productCostPersist->save($productCost);
        } else {
            $productCost->updateCosts($materialCost, $laborCost, $overheadCost);
            $this->productCostPersist->update($productCost);
        }

        // Dispatch event
        $this->eventDispatcher->dispatch(new ProductCostCalculatedEvent(
            productId: $productId,
            costCenterId: $productCost->getCostCenterId(),
            periodId: $periodId,
            materialCost: $materialCost,
            laborCost: $laborCost,
            overheadCost: $overheadCost,
            totalCost: $productCost->getTotalCost(),
            unitCost: $productCost->getUnitCost(),
            costType: 'standard',
            tenantId: $productCost->getTenantId(),
            occurredAt: new \DateTimeImmutable()
        ));

        $this->logger->info('Standard cost calculated', [
            'product_id' => $productId,
            'material_cost' => $materialCost,
            'labor_cost' => $laborCost,
            'overhead_cost' => $overheadCost,
            'total_cost' => $productCost->getTotalCost(),
        ]);

        return $productCost;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateActualCost(string $productId, string $periodId): ProductCost
    {
        // Get actual material cost from inventory transactions
        $materialCost = $this->getActualMaterialCost($productId, $periodId);

        // Get actual labor cost from manufacturing
        $laborCost = $this->getActualLaborCost($productId, $periodId);

        // Calculate actual overhead cost
        $overheadCost = $this->calculateActualOverheadCost($productId, $periodId, $materialCost, $laborCost);

        // Create or update product cost
        $productCost = $this->productCostQuery->findByProduct($productId, $periodId);

        if ($productCost === null) {
            $productCost = new ProductCost(
                id: $this->generateId(),
                productId: $productId,
                costCenterId: $this->getDefaultCostCenter($productId),
                periodId: $periodId,
                tenantId: $this->getTenantId($productId),
                costType: 'actual',
                currency: 'USD',
                materialCost: $materialCost,
                laborCost: $laborCost,
                overheadCost: $overheadCost
            );
            $this->productCostPersist->save($productCost);
        } else {
            $productCost->updateCosts($materialCost, $laborCost, $overheadCost);
            $this->productCostPersist->update($productCost);
        }

        // Dispatch event
        $this->eventDispatcher->dispatch(new ProductCostCalculatedEvent(
            productId: $productId,
            costCenterId: $productCost->getCostCenterId(),
            periodId: $periodId,
            materialCost: $materialCost,
            laborCost: $laborCost,
            overheadCost: $overheadCost,
            totalCost: $productCost->getTotalCost(),
            unitCost: $productCost->getUnitCost(),
            costType: 'actual',
            tenantId: $productCost->getTenantId(),
            occurredAt: new \DateTimeImmutable()
        ));

        $this->logger->info('Actual cost calculated', [
            'product_id' => $productId,
            'material_cost' => $materialCost,
            'labor_cost' => $laborCost,
            'overhead_cost' => $overheadCost,
            'total_cost' => $productCost->getTotalCost(),
        ]);

        return $productCost;
    }

    /**
     * {@inheritdoc}
     */
    public function rollup(string $productId, string $periodId): ProductCostSnapshot
    {
        $this->logger->info('Performing cost rollup', [
            'product_id' => $productId,
            'period_id' => $periodId,
        ]);

        // Get base product cost
        $productCost = $this->productCostQuery->findByProduct($productId, $periodId);
        
        if ($productCost === null) {
            throw new \RuntimeException(
                sprintf('Product cost not found for product %s in period %s', $productId, $periodId)
            );
        }

        // Get Bill of Materials (BOM) for multi-level rollup
        $components = $this->manufacturingProvider->getBillOfMaterials($productId);

        $totalMaterial = $productCost->getMaterialCost();
        $totalLabor = $productCost->getLaborCost();
        $totalOverhead = $productCost->getOverheadCost();

        // Roll up component costs
        $level = 0;
        foreach ($components as $component) {
            $componentCost = $this->productCostQuery->findByProduct(
                $component['product_id'],
                $periodId
            );

            if ($componentCost !== null) {
                $quantity = $component['quantity'] ?? 1;
                $totalMaterial += $componentCost->getMaterialCost() * $quantity;
                $totalLabor += $componentCost->getLaborCost() * $quantity;
                $totalOverhead += $componentCost->getOverheadCost() * $quantity;
                $level = max($level, 1);
            }
        }

        $totalCost = $totalMaterial + $totalLabor + $totalOverhead;

        // Create snapshot
        $snapshot = new ProductCostSnapshot(
            productId: $productId,
            periodId: $periodId,
            materialCost: $totalMaterial,
            laborCost: $totalLabor,
            overheadCost: $totalOverhead,
            totalCost: $totalCost,
            unitCost: 0.0, // Will be calculated when quantity is provided
            level: $level,
            capturedAt: new \DateTimeImmutable()
        );

        $this->logger->info('Cost rollup completed', [
            'product_id' => $productId,
            'level' => $level,
            'total_cost' => $totalCost,
        ]);

        return $snapshot;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateUnitCost(
        string $productId,
        string $periodId,
        float $quantity
    ): float {
        $productCost = $this->productCostQuery->findByProduct($productId, $periodId);

        if ($productCost === null) {
            throw new \RuntimeException(
                sprintf('Product cost not found for product %s in period %s', $productId, $periodId)
            );
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero');
        }

        $unitCost = $productCost->getTotalCost() / $quantity;
        $productCost->calculateUnitCost($quantity);
        $this->productCostPersist->update($productCost);

        return $unitCost;
    }

    /**
     * Get standard material cost
     */
    private function getMaterialCost(string $productId, string $periodId): float
    {
        return $this->inventoryProvider->getStandardCost($productId, $periodId);
    }

    /**
     * Get actual material cost
     */
    private function getActualMaterialCost(string $productId, string $periodId): float
    {
        return $this->inventoryProvider->getActualCost($productId, $periodId);
    }

    /**
     * Get standard labor cost
     */
    private function getLaborCost(string $productId, string $periodId): float
    {
        return $this->manufacturingProvider->getStandardLaborCost($productId, $periodId);
    }

    /**
     * Get actual labor cost
     */
    private function getActualLaborCost(string $productId, string $periodId): float
    {
        return $this->manufacturingProvider->getActualLaborCost($productId, $periodId);
    }

    /**
     * Calculate overhead cost (standard)
     */
    private function calculateOverheadCost(
        string $productId,
        string $periodId,
        float $materialCost,
        float $laborCost
    ): float {
        // Get overhead rate from manufacturing provider
        $overheadRate = $this->manufacturingProvider->getOverheadRate($productId, $periodId);
        
        // Apply overhead based on labor cost (common approach)
        return ($materialCost + $laborCost) * $overheadRate;
    }

    /**
     * Calculate actual overhead cost
     */
    private function calculateActualOverheadCost(
        string $productId,
        string $periodId,
        float $materialCost,
        float $laborCost
    ): float {
        // Get actual overhead allocation
        return $this->manufacturingProvider->getActualOverheadCost($productId, $periodId);
    }

    /**
     * Get default cost center for product
     */
    private function getDefaultCostCenter(string $productId): string
    {
        // This would typically come from product data
        return 'cc_default';
    }

    /**
     * Get tenant ID for product
     */
    private function getTenantId(string $productId): string
    {
        // This would typically come from product data
        return 'tenant_default';
    }

    /**
     * Generate unique ID
     */
    private function generateId(): string
    {
        return 'pc_' . bin2hex(random_bytes(16));
    }
}
