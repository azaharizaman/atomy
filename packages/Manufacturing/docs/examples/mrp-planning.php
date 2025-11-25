<?php

declare(strict_types=1);

/**
 * MRP Planning Example
 *
 * This example demonstrates Material Requirements Planning (MRP)
 * including demand calculation, lot sizing, and planned order generation.
 */

use Nexus\Manufacturing\Services\MrpEngine;
use Nexus\Manufacturing\Services\BomManager;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\Enums\LotSizingStrategy;
use Nexus\Manufacturing\Enums\PlanningZone;

// =============================================================================
// Setup - In your application, these would be injected via DI
// =============================================================================

/** @var MrpEngine $mrpEngine */
/** @var BomManager $bomManager */

// =============================================================================
// Example 1: Configure Planning Horizon
// =============================================================================

echo "Example 1: Configuring Planning Horizon\n";
echo str_repeat('-', 50) . "\n";

$horizon = new PlanningHorizon(
    startDate: new DateTimeImmutable('today'),
    endDate: new DateTimeImmutable('+90 days'),
    bucketSizeDays: 7,
    frozenZoneDays: 14,
    slushyZoneDays: 28
);

echo "Planning horizon: {$horizon->startDate->format('Y-m-d')} to {$horizon->endDate->format('Y-m-d')}\n";
echo "Bucket size: {$horizon->bucketSizeDays} days\n";
echo "Frozen zone: {$horizon->frozenZoneDays} days (no automatic changes)\n";
echo "Slushy zone: {$horizon->slushyZoneDays} days (changes with approval)\n";
echo "Liquid zone: After {$horizon->slushyZoneDays} days (automatic planning)\n\n";

// =============================================================================
// Example 2: Run MRP for a Product
// =============================================================================

echo "Example 2: Running MRP for a Product\n";
echo str_repeat('-', 50) . "\n";

$productId = 'WIDGET-001';

// Run MRP calculation
$mrpResult = $mrpEngine->runMrp($productId, $horizon);

echo "MRP Results for {$productId}:\n";
echo "  Gross Requirements: {$mrpResult->getGrossRequirements()} units\n";
echo "  Scheduled Receipts: {$mrpResult->getScheduledReceipts()} units\n";
echo "  On Hand: {$mrpResult->getOnHand()} units\n";
echo "  Net Requirements: {$mrpResult->getNetRequirements()} units\n\n";

// =============================================================================
// Example 3: View Planned Orders
// =============================================================================

echo "Example 3: Viewing Planned Orders\n";
echo str_repeat('-', 50) . "\n";

$plannedOrders = $mrpResult->getPlannedOrders();

echo sprintf(
    "%-12s %-12s %-12s %12s %-10s\n",
    'Order Date',
    'Due Date',
    'Product',
    'Quantity',
    'Zone'
);
echo str_repeat('-', 65) . "\n";

foreach ($plannedOrders as $order) {
    $zone = $mrpEngine->getZoneForDate($order->getDueDate(), $horizon);
    
    echo sprintf(
        "%-12s %-12s %-12s %12.2f %-10s\n",
        $order->getOrderDate()->format('Y-m-d'),
        $order->getDueDate()->format('Y-m-d'),
        $order->getProductId(),
        $order->getQuantity(),
        $zone->value
    );
}

echo "\n";

// =============================================================================
// Example 4: Lot Sizing Strategies
// =============================================================================

echo "Example 4: Lot Sizing Strategies\n";
echo str_repeat('-', 50) . "\n";

$demand = 150.0;

// Lot-for-Lot (exact demand)
$lotForLot = $mrpEngine->calculateLotSize(
    $demand,
    LotSizingStrategy::LOT_FOR_LOT,
    []
);
echo "Lot-for-Lot: {$lotForLot} units\n";

// Fixed Order Quantity
$fixedQty = $mrpEngine->calculateLotSize(
    $demand,
    LotSizingStrategy::FIXED_ORDER_QUANTITY,
    ['fixedQuantity' => 200.0]
);
echo "Fixed Order Quantity (200): {$fixedQty} units\n";

// Economic Order Quantity (EOQ)
$eoq = $mrpEngine->calculateLotSize(
    $demand,
    LotSizingStrategy::ECONOMIC_ORDER_QUANTITY,
    [
        'annualDemand' => 2000.0,
        'orderingCost' => 50.0,
        'holdingCostPerUnit' => 2.0,
    ]
);
echo "Economic Order Quantity: {$eoq} units\n";

// Period Order Quantity (POQ)
$poq = $mrpEngine->calculateLotSize(
    $demand,
    LotSizingStrategy::PERIOD_ORDER_QUANTITY,
    [
        'coveragePeriods' => 3,
        'averageDemandPerPeriod' => 50.0,
    ]
);
echo "Period Order Quantity (3 periods): {$poq} units\n\n";

// =============================================================================
// Example 5: Multi-Level BOM Explosion in MRP
// =============================================================================

echo "Example 5: Multi-Level MRP\n";
echo str_repeat('-', 50) . "\n";

// Run full MRP explosion for product and all components
$fullMrpResults = $mrpEngine->runFullMrp(
    productId: 'WIDGET-001',
    horizon: $horizon,
    explodeBom: true
);

echo "Multi-level MRP results:\n\n";
echo sprintf(
    "%-5s %-20s %12s %12s %12s\n",
    'Level',
    'Product',
    'Gross Req',
    'On Hand',
    'Net Req'
);
echo str_repeat('-', 70) . "\n";

foreach ($fullMrpResults as $result) {
    echo sprintf(
        "%-5d %-20s %12.2f %12.2f %12.2f\n",
        $result->getLevel(),
        $result->getProductId(),
        $result->getGrossRequirements(),
        $result->getOnHand(),
        $result->getNetRequirements()
    );
}

echo "\n";

// =============================================================================
// Example 6: Action Messages
// =============================================================================

echo "Example 6: MRP Action Messages\n";
echo str_repeat('-', 50) . "\n";

$actionMessages = $mrpEngine->getActionMessages($productId, $horizon);

echo "Action messages requiring attention:\n\n";

foreach ($actionMessages as $message) {
    $icon = match ($message->getSeverity()) {
        'critical' => '🔴',
        'warning' => '🟡',
        'info' => '🔵',
        default => '⚪',
    };
    
    echo "{$icon} [{$message->getType()}] {$message->getMessage()}\n";
    
    if ($message->getSuggestedAction()) {
        echo "   Suggested: {$message->getSuggestedAction()}\n";
    }
}

echo "\n";

// =============================================================================
// Example 7: Pegging (Where-Used Analysis)
// =============================================================================

echo "Example 7: Demand Pegging\n";
echo str_repeat('-', 50) . "\n";

$componentId = 'HOUSING-001';
$pegging = $mrpEngine->getPegging($componentId, $horizon);

echo "Pegging for {$componentId}:\n\n";
echo sprintf(
    "%-15s %-12s %12s %-10s\n",
    'Parent',
    'Date',
    'Quantity',
    'Source'
);
echo str_repeat('-', 55) . "\n";

foreach ($pegging as $peg) {
    echo sprintf(
        "%-15s %-12s %12.2f %-10s\n",
        $peg->getParentProductId(),
        $peg->getRequiredDate()->format('Y-m-d'),
        $peg->getQuantity(),
        $peg->getSource()
    );
}

echo "\n";

// =============================================================================
// Example 8: Safety Stock Calculation
// =============================================================================

echo "Example 8: Safety Stock\n";
echo str_repeat('-', 50) . "\n";

$safetyStock = $mrpEngine->calculateSafetyStock(
    productId: 'WIDGET-001',
    serviceLevel: 0.95, // 95% service level
    historicalPeriods: 12
);

echo "Safety stock calculation for WIDGET-001:\n";
echo "  Service level: 95%\n";
echo "  Historical periods analyzed: 12 months\n";
echo "  Recommended safety stock: {$safetyStock} units\n\n";

// =============================================================================
// Example 9: Convert Planned Orders to Work Orders
// =============================================================================

echo "Example 9: Converting Planned Orders\n";
echo str_repeat('-', 50) . "\n";

// Get planned orders in liquid zone (can be automatically released)
$liquidZoneOrders = $mrpEngine->getPlannedOrdersByZone(
    $productId,
    PlanningZone::LIQUID,
    $horizon
);

echo "Planned orders in liquid zone (can be released):\n\n";

foreach ($liquidZoneOrders as $plannedOrder) {
    echo "Converting planned order {$plannedOrder->getId()}...\n";
    
    // In real application, you would inject WorkOrderManager
    // $workOrder = $workOrderManager->createFromPlannedOrder($plannedOrder);
    
    echo "  Product: {$plannedOrder->getProductId()}\n";
    echo "  Quantity: {$plannedOrder->getQuantity()}\n";
    echo "  Due: {$plannedOrder->getDueDate()->format('Y-m-d')}\n";
    echo "  -> Would create Work Order\n\n";
}

echo "MRP planning example complete!\n";
