<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

/**
 * Lot Sizing Strategy enum.
 *
 * Defines strategies for determining order quantities in MRP.
 */
enum LotSizingStrategy: string
{
    /**
     * Lot-for-Lot: Order exactly what is needed.
     * Minimizes inventory holding cost.
     */
    case LOT_FOR_LOT = 'lot_for_lot';

    /**
     * Fixed Order Quantity: Always order the same quantity.
     * Used when setup costs favor consistent batch sizes.
     */
    case FIXED_ORDER_QUANTITY = 'fixed_order_quantity';

    /**
     * Economic Order Quantity (EOQ): Balance setup and holding costs.
     * Classic optimization for minimizing total inventory cost.
     */
    case ECONOMIC_ORDER_QUANTITY = 'economic_order_quantity';

    /**
     * Period Order Quantity: Cover multiple periods in one order.
     * Reduces order frequency while managing inventory levels.
     */
    case PERIOD_ORDER_QUANTITY = 'period_order_quantity';

    /**
     * Least Unit Cost (LUC): Minimize cost per unit over time.
     * Dynamic lot sizing considering both setup and holding.
     */
    case LEAST_UNIT_COST = 'least_unit_cost';

    /**
     * Get default parameters for the strategy.
     *
     * @return array<string, mixed>
     */
    public function getDefaultParameters(): array
    {
        return match ($this) {
            self::LOT_FOR_LOT => [],
            self::FIXED_ORDER_QUANTITY => ['quantity' => 100.0],
            self::ECONOMIC_ORDER_QUANTITY => ['orderingCost' => 50.0, 'holdingCostRate' => 0.25],
            self::PERIOD_ORDER_QUANTITY => ['periods' => 4],
            self::LEAST_UNIT_COST => ['orderingCost' => 50.0, 'holdingCostRate' => 0.25, 'maxPeriods' => 12],
        };
    }

    /**
     * Get required parameters for the strategy.
     *
     * @return array<string>
     */
    public function getRequiredParameters(): array
    {
        return match ($this) {
            self::LOT_FOR_LOT => [],
            self::FIXED_ORDER_QUANTITY => ['quantity'],
            self::ECONOMIC_ORDER_QUANTITY => ['orderingCost', 'holdingCostRate', 'annualDemand'],
            self::PERIOD_ORDER_QUANTITY => ['periods'],
            self::LEAST_UNIT_COST => ['orderingCost', 'holdingCostRate'],
        };
    }

    /**
     * Check if strategy requires demand history.
     */
    public function requiresDemandHistory(): bool
    {
        return match ($this) {
            self::ECONOMIC_ORDER_QUANTITY => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOT_FOR_LOT => 'Lot-for-Lot',
            self::FIXED_ORDER_QUANTITY => 'Fixed Order Quantity',
            self::ECONOMIC_ORDER_QUANTITY => 'Economic Order Quantity (EOQ)',
            self::PERIOD_ORDER_QUANTITY => 'Period Order Quantity',
            self::LEAST_UNIT_COST => 'Least Unit Cost',
        };
    }

    /**
     * Get description of the strategy.
     */
    public function description(): string
    {
        return match ($this) {
            self::LOT_FOR_LOT => 'Order exactly what is needed, minimizing inventory',
            self::FIXED_ORDER_QUANTITY => 'Always order the same quantity',
            self::ECONOMIC_ORDER_QUANTITY => 'Balance setup and holding costs using classic EOQ formula',
            self::PERIOD_ORDER_QUANTITY => 'Cover multiple periods in one order',
            self::LEAST_UNIT_COST => 'Dynamically minimize cost per unit over time',
        };
    }

    /**
     * Get best use cases for this strategy.
     *
     * @return array<string>
     */
    public function getBestUseCases(): array
    {
        return match ($this) {
            self::LOT_FOR_LOT => [
                'Expensive items with low holding capacity',
                'Items with short shelf life',
                'Make-to-order production',
            ],
            self::FIXED_ORDER_QUANTITY => [
                'Stable demand patterns',
                'High setup/ordering costs',
                'Supplier minimum order requirements',
            ],
            self::ECONOMIC_ORDER_QUANTITY => [
                'Stable, predictable demand',
                'Known setup and holding costs',
                'Independent demand items',
            ],
            self::PERIOD_ORDER_QUANTITY => [
                'Reducing order frequency',
                'Consolidating shipments',
                'Variable demand with periodic patterns',
            ],
            self::LEAST_UNIT_COST => [
                'Variable demand patterns',
                'Complex cost trade-offs',
                'Dynamic production environments',
            ],
        };
    }
}
