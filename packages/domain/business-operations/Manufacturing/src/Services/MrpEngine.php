<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\MrpEngineInterface;
use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\InventoryDataProviderInterface;
use Nexus\Manufacturing\Contracts\DemandDataProviderInterface;
use Nexus\Manufacturing\Enums\LotSizingStrategy;
use Nexus\Manufacturing\Exceptions\BomNotFoundException;
use Nexus\Manufacturing\Exceptions\MrpCalculationException;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\ValueObjects\MaterialRequirement;
use Nexus\Manufacturing\ValueObjects\PlannedOrder;
use Nexus\Manufacturing\ValueObjects\MrpResult;

/**
 * MRP Engine implementation.
 *
 * Calculates material requirements and generates planned orders.
 */
final readonly class MrpEngine implements MrpEngineInterface
{
    public function __construct(
        private BomManagerInterface $bomManager,
        private InventoryDataProviderInterface $inventoryProvider,
        private DemandDataProviderInterface $demandProvider,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function calculate(
        string $productId,
        PlanningHorizon $horizon,
        LotSizingStrategy $lotSizing = LotSizingStrategy::LOT_FOR_LOT,
        array $lotSizingParameters = []
    ): MrpResult {
        $calculatedAt = new \DateTimeImmutable();
        $warnings = [];
        $errors = [];
        $materialRequirements = [];
        $plannedOrders = [];

        try {
            // Get gross requirements from demand
            $grossRequirements = $this->getGrossRequirements($productId, $horizon);

            // Get current inventory and scheduled receipts
            $onHand = $this->inventoryProvider->getOnHandQuantity($productId);
            $safetyStock = $this->inventoryProvider->getSafetyStock($productId);
            $scheduledReceipts = $this->inventoryProvider->getScheduledReceipts($productId, $horizon->endDate);
            $leadTimeDays = $this->inventoryProvider->getLeadTimeDays($productId);

            // If no lead time, use default of 1 day with warning
            if ($leadTimeDays === 0) {
                $leadTimeDays = 1;
                $warnings[] = "Using default lead time of 1 day for product '{$productId}'";
            }

            // Net requirements calculation
            foreach ($grossRequirements as $requirementDate => $grossQty) {
                $requiredDate = new \DateTimeImmutable($requirementDate);

                // Calculate available = on hand + scheduled receipts - safety stock
                $available = $onHand + $this->getScheduledReceiptsBeforeDate($scheduledReceipts, $requiredDate) - $safetyStock;

                // Net requirement = max(0, gross - available)
                $netRequirement = max(0, $grossQty - $available);

                // Calculate order date (required date - lead time)
                $orderDate = $requiredDate->modify("-{$leadTimeDays} days");

                // Only create requirement if within horizon
                if ($orderDate < $horizon->startDate) {
                    $orderDate = $horizon->startDate;
                    $warnings[] = "Order date for {$requirementDate} adjusted to horizon start";
                }

                $materialRequirements[] = new MaterialRequirement(
                    productId: $productId,
                    grossRequirement: $grossQty,
                    netRequirement: $netRequirement,
                    requiredDate: $requiredDate,
                    orderDate: $orderDate,
                    onHandQuantity: $onHand,
                    scheduledReceipts: $this->getScheduledReceiptsBeforeDate($scheduledReceipts, $requiredDate),
                    safetyStock: $safetyStock,
                    level: 0,
                );

                // Update projected on-hand for next iteration
                $onHand = max(0, $available - $grossQty);

                // Generate planned order if net requirement exists
                if ($netRequirement > 0) {
                    $lotSizedQuantity = $this->applyLotSizing($netRequirement, $lotSizing, $lotSizingParameters);

                    $plannedOrders[] = new PlannedOrder(
                        productId: $productId,
                        quantity: $lotSizedQuantity,
                        startDate: $orderDate,
                        dueDate: $requiredDate,
                        orderType: $this->determineOrderType($productId),
                        level: 0,
                        lotSizingStrategy: $lotSizing->value,
                        originalRequirement: $netRequirement,
                    );

                    // Add lot sizing excess back to projected inventory
                    $onHand += ($lotSizedQuantity - $netRequirement);
                }
            }

            // Explode BOM for manufactured products and add component requirements
            foreach ($plannedOrders as $order) {
                if ($order->isManufacturing()) {
                    $componentRequirements = $this->explodeRequirements(
                        $productId,
                        $order->quantity,
                        $order->startDate,
                        $horizon,
                        $lotSizing,
                        $lotSizingParameters,
                        1 // Starting at level 1 for components
                    );
                    $materialRequirements = [...$materialRequirements, ...$componentRequirements['requirements']];
                    $plannedOrders = [...$plannedOrders, ...$componentRequirements['orders']];
                    $warnings = [...$warnings, ...$componentRequirements['warnings']];
                }
            }

        } catch (MrpCalculationException $e) {
            $errors = $e->getErrors();
        } catch (\Throwable $e) {
            $errors[] = "Calculation error: {$e->getMessage()}";
        }

        return new MrpResult(
            productId: $productId,
            plannedOrders: $plannedOrders,
            materialRequirements: $materialRequirements,
            warnings: $warnings,
            errors: $errors,
            calculatedAt: $calculatedAt,
            parameters: [
                'horizon' => $horizon->toArray(),
                'lotSizing' => $lotSizing->value,
                'lotSizingParameters' => $lotSizingParameters,
            ],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function calculateMultiple(array $productIds, PlanningHorizon $horizon, LotSizingStrategy $lotSizing = LotSizingStrategy::LOT_FOR_LOT): array
    {
        $results = [];

        foreach ($productIds as $productId) {
            $results[$productId] = $this->calculate($productId, $horizon, $lotSizing);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate(PlanningHorizon $horizon, ?array $productIds = null, bool $deleteExisting = true): array
    {
        // If no products specified, get all planned/manufactured products
        if ($productIds === null) {
            $productIds = $this->demandProvider->getMasterScheduledProducts($horizon);
        }

        $results = [];

        foreach ($productIds as $productId) {
            // Delete existing planned orders if requested
            if ($deleteExisting) {
                $this->demandProvider->deletePlannedOrders($productId, $horizon);
            }

            // Calculate new MRP
            $result = $this->calculate($productId, $horizon);
            $results[$productId] = $result;

            // Store generated planned orders
            foreach ($result->plannedOrders as $order) {
                $this->demandProvider->savePlannedOrder($order);
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function netChange(string $productId, float $quantityChange, \DateTimeImmutable $effectiveDate): MrpResult
    {
        // Create a mini-horizon from effective date
        $horizon = new PlanningHorizon(
            startDate: $effectiveDate,
            endDate: $effectiveDate->modify('+90 days'),
        );

        return $this->calculate($productId, $horizon);
    }

    /**
     * {@inheritdoc}
     */
    public function pegging(string $productId, \DateTimeImmutable $date): array
    {
        $pegging = [];

        // Get all demand sources for this product on this date
        $demandSources = $this->demandProvider->getDemandSources($productId, $date);

        foreach ($demandSources as $source) {
            $pegging[] = [
                'productId' => $productId,
                'date' => $date->format('Y-m-d'),
                'sourceType' => $source['type'], // 'sales_order', 'work_order', 'forecast'
                'sourceId' => $source['id'],
                'quantity' => $source['quantity'],
            ];
        }

        // Get parent products where this is a component
        $whereUsed = $this->bomManager->whereUsed($productId);

        foreach ($whereUsed as $parentBom) {
            $parentDemand = $this->demandProvider->getDemandSources($parentBom->getProductId(), $date);
            foreach ($parentDemand as $source) {
                $pegging[] = [
                    'productId' => $productId,
                    'date' => $date->format('Y-m-d'),
                    'sourceType' => 'derived_from_' . $source['type'],
                    'sourceId' => $source['id'],
                    'parentProductId' => $parentBom->getProductId(),
                    'quantity' => $source['quantity'],
                ];
            }
        }

        return $pegging;
    }

    /**
     * Get gross requirements from demand sources.
     *
     * @return array<string, float> Date => quantity mapping
     */
    private function getGrossRequirements(string $productId, PlanningHorizon $horizon): array
    {
        $requirements = [];

        // Get independent demand (sales orders, forecasts)
        $independentDemand = $this->demandProvider->getIndependentDemand($productId, $horizon);

        foreach ($independentDemand as $demand) {
            $date = $demand['date'];
            $requirements[$date] = ($requirements[$date] ?? 0) + $demand['quantity'];
        }

        // Get dependent demand (from work orders using this component)
        $dependentDemand = $this->demandProvider->getDependentDemand($productId, $horizon);

        foreach ($dependentDemand as $demand) {
            $date = $demand['date'];
            $requirements[$date] = ($requirements[$date] ?? 0) + $demand['quantity'];
        }

        // Sort by date
        ksort($requirements);

        return $requirements;
    }

    /**
     * Get scheduled receipts before a given date.
     *
     * @param array<array{date: string, quantity: float}> $receipts
     */
    private function getScheduledReceiptsBeforeDate(array $receipts, \DateTimeImmutable $beforeDate): float
    {
        $total = 0.0;

        foreach ($receipts as $receipt) {
            $receiptDate = new \DateTimeImmutable($receipt['date']);
            if ($receiptDate < $beforeDate) {
                $total += $receipt['quantity'];
            }
        }

        return $total;
    }

    /**
     * Apply lot sizing strategy to net requirement.
     *
     * @param array<string, mixed> $parameters
     */
    private function applyLotSizing(float $netRequirement, LotSizingStrategy $strategy, array $parameters): float
    {
        return match ($strategy) {
            LotSizingStrategy::LOT_FOR_LOT => $netRequirement,

            LotSizingStrategy::FIXED_ORDER_QUANTITY => max(
                $netRequirement,
                $parameters['fixedQuantity'] ?? $netRequirement
            ),

            LotSizingStrategy::ECONOMIC_ORDER_QUANTITY => $this->calculateEOQ(
                $netRequirement,
                $parameters['annualDemand'] ?? $netRequirement * 12,
                $parameters['orderingCost'] ?? 100,
                $parameters['holdingCost'] ?? 10
            ),

            LotSizingStrategy::PERIOD_ORDER_QUANTITY => $netRequirement * ($parameters['periods'] ?? 1),

            LotSizingStrategy::LEAST_UNIT_COST => $this->calculateLeastUnitCost(
                $netRequirement,
                $parameters['orderingCost'] ?? 50.0,
                $parameters['holdingCostRate'] ?? 0.25
            ),
        };
    }

    /**
     * Calculate Economic Order Quantity.
     */
    private function calculateEOQ(float $demand, float $annualDemand, float $orderingCost, float $holdingCost): float
    {
        // EOQ = sqrt((2 * D * S) / H)
        // D = annual demand, S = ordering cost, H = holding cost per unit
        $eoq = sqrt((2 * $annualDemand * $orderingCost) / $holdingCost);

        return max($demand, $eoq);
    }

    /**
     * Calculate Least Unit Cost lot sizing.
     *
     * Minimizes the total cost per unit by balancing ordering and holding costs.
     */
    private function calculateLeastUnitCost(float $demand, float $orderingCost, float $holdingCostRate): float
    {
        // Simple LUC implementation: use EOQ-like calculation with provided demand
        if ($holdingCostRate <= 0) {
            return $demand;
        }

        // Use a simpler cost optimization approach
        $annualDemand = $demand * 12;
        $eoq = sqrt((2 * $annualDemand * $orderingCost) / $holdingCostRate);

        return max($demand, $eoq);
    }

    /**
     * Determine if product should be manufactured or purchased.
     */
    private function determineOrderType(string $productId): string
    {
        try {
            $this->bomManager->findByProductId($productId);
            return 'manufacturing';
        } catch (BomNotFoundException) {
            return 'purchase';
        }
    }

    /**
     * Explode BOM requirements for component materials.
     *
     * @param array<string, mixed> $lotSizingParameters
     * @return array{requirements: array<MaterialRequirement>, orders: array<PlannedOrder>, warnings: array<string>}
     */
    private function explodeRequirements(
        string $productId,
        float $quantity,
        \DateTimeImmutable $parentOrderDate,
        PlanningHorizon $horizon,
        LotSizingStrategy $lotSizing,
        array $lotSizingParameters,
        int $level
    ): array {
        $requirements = [];
        $orders = [];
        $warnings = [];

        if ($level > 10) {
            $warnings[] = "Maximum BOM explosion level (10) reached for product '{$productId}'";
            return ['requirements' => $requirements, 'orders' => $orders, 'warnings' => $warnings];
        }

        try {
            // Get BOM for the product, then explode it
            $bom = $this->bomManager->findByProductId($productId);
            $bomLines = $this->bomManager->explode($bom->getId(), $quantity);

            foreach ($bomLines as $line) {
                $componentId = $line['productId'];
                $requiredQty = $line['quantity'];

                $onHand = $this->inventoryProvider->getOnHandQuantity($componentId);
                $safetyStock = $this->inventoryProvider->getSafetyStock($componentId);
                $leadTimeDays = $this->inventoryProvider->getLeadTimeDays($componentId);

                // Calculate order date considering lead time
                $orderDate = $parentOrderDate->modify("-{$leadTimeDays} days");
                if ($orderDate < $horizon->startDate) {
                    $orderDate = $horizon->startDate;
                    $warnings[] = "Component '{$componentId}' order date adjusted to horizon start";
                }

                // Calculate net requirement
                $netRequirement = max(0, $requiredQty - ($onHand - $safetyStock));

                $requirements[] = new MaterialRequirement(
                    productId: $componentId,
                    grossRequirement: $requiredQty,
                    netRequirement: $netRequirement,
                    requiredDate: $parentOrderDate,
                    orderDate: $orderDate,
                    onHandQuantity: $onHand,
                    scheduledReceipts: 0.0,
                    safetyStock: $safetyStock,
                    level: $level,
                    parentProductId: $productId,
                );

                // Generate planned order if needed
                if ($netRequirement > 0) {
                    $lotSizedQty = $this->applyLotSizing($netRequirement, $lotSizing, $lotSizingParameters);

                    $orders[] = new PlannedOrder(
                        productId: $componentId,
                        quantity: $lotSizedQty,
                        startDate: $orderDate,
                        dueDate: $parentOrderDate,
                        orderType: $this->determineOrderType($componentId),
                        level: $level,
                        lotSizingStrategy: $lotSizing->value,
                        originalRequirement: $netRequirement,
                    );

                    // Recursively explode if manufactured
                    if ($this->determineOrderType($componentId) === 'manufacturing') {
                        $subResults = $this->explodeRequirements(
                            $componentId,
                            $lotSizedQty,
                            $orderDate,
                            $horizon,
                            $lotSizing,
                            $lotSizingParameters,
                            $level + 1
                        );
                        $requirements = [...$requirements, ...$subResults['requirements']];
                        $orders = [...$orders, ...$subResults['orders']];
                        $warnings = [...$warnings, ...$subResults['warnings']];
                    }
                }
            }
        } catch (BomNotFoundException) {
            // No BOM - this is a raw material, already handled
        }

        return ['requirements' => $requirements, 'orders' => $orders, 'warnings' => $warnings];
    }
}
