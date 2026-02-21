<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Services;

use Nexus\CostAccounting\Contracts\Integration\InventoryDataProviderInterface;
use Nexus\CostAccounting\Contracts\Integration\ManufacturingDataProviderInterface;
use Nexus\CostAccounting\Contracts\ProductCostCalculatorInterface;
use Nexus\CostAccounting\Contracts\ProductCostPersistInterface;
use Nexus\CostAccounting\Contracts\ProductCostQueryInterface;
use Nexus\CostAccounting\Contracts\ProductQueryInterface;
use Nexus\CostAccounting\Entities\ProductCost;
use Nexus\CostAccounting\Enums\CostType;
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
    private ?ProductQueryInterface $productQuery;

    public function __construct(
        private ProductCostQueryInterface $productCostQuery,
        private ProductCostPersistInterface $productCostPersist,
        private InventoryDataProviderInterface $inventoryProvider,
        private ManufacturingDataProviderInterface $manufacturingProvider,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        ?ProductQueryInterface $productQuery = null
    ) {
        $this->productQuery = $productQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function calculate(
        string $productId,
        string $periodId,
        CostType $costType = CostType::Standard
    ): ProductCost {
        $this->logger->info('Calculating product cost', [
            'product_id' => $productId,
            'period_id' => $periodId,
            'cost_type' => $costType->value,
        ]);

        return match ($costType) {
            CostType::Standard => $this->calculateStandardCost($productId, $periodId),
            CostType::Actual => $this->calculateActualCost($productId, $periodId),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function calculateStandardCost(string $productId, string $periodId): ProductCost
    {
        // Get product for currency and cost center
        $product = $this->getProduct($productId);
        $currency = $product->getCurrency() ?? 'USD';

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
                costType: CostType::Standard,
                currency: $currency,
                materialCost: $materialCost,
                laborCost: $laborCost,
                overheadCost: $overheadCost
            );
            $this->productCostPersist->save($productCost);
        } else {
            $productCost = $productCost->withCosts($materialCost, $laborCost, $overheadCost);
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
            costType: CostType::Standard->value,
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
        // Get product for currency and cost center
        $product = $this->getProduct($productId);
        $currency = $product->getCurrency() ?? 'USD';

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
                costType: CostType::Actual,
                currency: $currency,
                materialCost: $materialCost,
                laborCost: $laborCost,
                overheadCost: $overheadCost
            );
            $this->productCostPersist->save($productCost);
        } else {
            $productCost = $productCost->withCosts($materialCost, $laborCost, $overheadCost);
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
            costType: CostType::Actual->value,
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

            if ($componentCost === null) {
                $this->logger->warning('Component cost not found for rollup', [
                    'product_id' => $component['product_id'],
                    'period_id' => $periodId,
                ]);
                continue;
            }

            $quantity = $component['quantity'] ?? 1;
            $totalMaterial += $componentCost->getMaterialCost() * $quantity;
            $totalLabor += $componentCost->getLaborCost() * $quantity;
            $totalOverhead += $componentCost->getOverheadCost() * $quantity;

            // Get child component's level and add 1
            $childLevel = $componentCost->getLevel() ?? 1;
            $level = max($level, $childLevel + 1);
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
        $productCost = $productCost->withUnitCost($quantity);
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
     * Calculate actual overhead cost.
     * Note: materialCost and laborCost parameters are intentionally unused.
     * The overhead is determined by the manufacturing provider.
     */
    private function calculateActualOverheadCost(
        string $productId,
        string $periodId,
        float $materialCost,  // Intentionally unused - kept for API consistency
        float $laborCost     // Intentionally unused - kept for API consistency
    ): float {
        // Get actual overhead allocation
        return $this->manufacturingProvider->getActualOverheadCost($productId, $periodId);
    }

    /**
     * Get product by ID
     *
     * @throws \BadMethodCallException When product query is not injected
     * @throws \InvalidArgumentException When product not found
     */
    private function getProduct(string $productId): object
    {
        if ($this->productQuery === null) {
            throw new \BadMethodCallException(
                'ProductQueryInterface is not injected. Cannot resolve product.'
            );
        }
        
        $product = $this->productQuery->findById($productId);
        if ($product === null) {
            throw new \InvalidArgumentException(
                sprintf('Product with ID %s not found', $productId)
            );
        }
        
        return $product;
    }

    /**
     * Get default cost center for product
     *
     * @throws \BadMethodCallException When product lookup is not implemented
     * @throws \InvalidArgumentException When product not found
     */
    private function getDefaultCostCenter(string $productId): string
    {
        return $this->getProduct($productId)->getDefaultCostCenterId();
    }

    /**
     * Get tenant ID for product
     *
     * @throws \BadMethodCallException When product lookup is not implemented
     * @throws \InvalidArgumentException When product not found
     */
    private function getTenantId(string $productId): string
    {
        return $this->getProduct($productId)->getTenantId();
    }

    /**
     * Generate unique ID
     */
    private function generateId(): string
    {
        return 'pc_' . bin2hex(random_bytes(16));
    }
}
