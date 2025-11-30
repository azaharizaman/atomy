<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\ValueObjects\PlannedOrder;

/**
 * Demand Data Provider interface.
 *
 * Provides demand data for MRP calculations.
 * Consumers must implement this interface to provide demand information.
 */
interface DemandDataProviderInterface
{
    /**
     * Get gross requirements for a product within a planning horizon.
     *
     * @param string $productId Product ID
     * @param PlanningHorizon $horizon Planning horizon
     * @return array<string, float> Date (Y-m-d) => quantity map
     */
    public function getGrossRequirements(string $productId, PlanningHorizon $horizon): array;

    /**
     * Get list of master scheduled products for MRP.
     *
     * @param PlanningHorizon $horizon Planning horizon
     * @return array<string> Product IDs
     */
    public function getMasterScheduledProducts(PlanningHorizon $horizon): array;

    /**
     * Delete existing planned orders for a product within a horizon.
     *
     * @param string $productId Product ID
     * @param PlanningHorizon $horizon Planning horizon
     */
    public function deletePlannedOrders(string $productId, PlanningHorizon $horizon): void;

    /**
     * Save a planned order.
     *
     * @param PlannedOrder $order Planned order to save
     */
    public function savePlannedOrder(PlannedOrder $order): void;

    /**
     * Get demand forecast for a product.
     *
     * @param string $productId Product ID
     * @param PlanningHorizon $horizon Planning horizon
     * @return array<string, float> Date (Y-m-d) => forecasted quantity
     */
    public function getForecastDemand(string $productId, PlanningHorizon $horizon): array;
}
