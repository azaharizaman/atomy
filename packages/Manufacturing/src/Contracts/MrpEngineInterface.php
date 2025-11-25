<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Enums\LotSizingStrategy;
use Nexus\Manufacturing\ValueObjects\MrpResult;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;

/**
 * MRP Engine interface.
 *
 * Provides Material Requirements Planning functionality.
 */
interface MrpEngineInterface
{
    /**
     * Calculate MRP for a single product.
     *
     * @param string $productId Product to calculate MRP for
     * @param PlanningHorizon $horizon Planning horizon
     * @param LotSizingStrategy $lotSizing Lot sizing strategy
     * @param array<string, mixed> $lotSizingParameters Strategy-specific parameters
     * @return MrpResult MRP calculation result
     */
    public function calculate(
        string $productId,
        PlanningHorizon $horizon,
        LotSizingStrategy $lotSizing = LotSizingStrategy::LOT_FOR_LOT,
        array $lotSizingParameters = []
    ): MrpResult;

    /**
     * Calculate MRP for multiple products.
     *
     * @param array<string> $productIds Products to calculate
     * @param PlanningHorizon $horizon Planning horizon
     * @param LotSizingStrategy $lotSizing Default lot sizing strategy
     * @return array<string, MrpResult> Product ID => result map
     */
    public function calculateMultiple(
        array $productIds,
        PlanningHorizon $horizon,
        LotSizingStrategy $lotSizing = LotSizingStrategy::LOT_FOR_LOT
    ): array;

    /**
     * Regenerate MRP for all or specified products.
     *
     * @param PlanningHorizon $horizon Planning horizon
     * @param array<string>|null $productIds Products to regenerate (null for all)
     * @param bool $deleteExisting Delete existing planned orders
     * @return array<string, MrpResult> Product ID => result map
     */
    public function regenerate(
        PlanningHorizon $horizon,
        ?array $productIds = null,
        bool $deleteExisting = true
    ): array;
}
