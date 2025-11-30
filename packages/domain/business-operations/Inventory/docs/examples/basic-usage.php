<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Nexus Inventory Package
 * 
 * This example demonstrates simple stock operations:
 * - Receiving stock from purchase order
 * - Issuing stock for sales order
 * - Checking stock availability
 */

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Inventory\Enums\IssueReason;
use Nexus\Currency\ValueObjects\Money;

// Assume dependency injection container provides these
/** @var StockManagerInterface $stockManager */
$stockManager = $container->get(StockManagerInterface::class);

$tenantId = 'tenant-abc123';
$productId = 'product-xyz789';
$warehouseId = 'warehouse-main';

// ========================================
// Example 1: Receive Stock from Purchase Order
// ========================================

echo "Example 1: Receiving Stock\n";
echo str_repeat('=', 50) . "\n";

$poNumber = 'PO-2024-001';
$quantityReceived = 100.0;
$unitCost = Money::of(25.50, 'MYR');

try {
    $stockManager->receiveStock(
        tenantId: $tenantId,
        productId: $productId,
        warehouseId: $warehouseId,
        quantity: $quantityReceived,
        unitCost: $unitCost,
        reference: $poNumber,
        receivedDate: new \DateTimeImmutable('2024-01-15')
    );
    
    echo "✅ Stock received successfully\n";
    echo "   Product: {$productId}\n";
    echo "   Quantity: {$quantityReceived}\n";
    echo "   Unit Cost: {$unitCost->getAmount()} {$unitCost->getCurrency()}\n";
    echo "   Reference: {$poNumber}\n\n";
    
    // StockReceivedEvent published automatically
    // GL Listener will post: DR Inventory Asset / CR GR-IR Clearing
    
} catch (\Exception $e) {
    echo "❌ Error receiving stock: {$e->getMessage()}\n\n";
}

// ========================================
// Example 2: Check Stock Availability
// ========================================

echo "Example 2: Checking Stock Availability\n";
echo str_repeat('=', 50) . "\n";

$totalStock = $stockManager->getTotalStock($tenantId, $productId, $warehouseId);
$availableStock = $stockManager->getAvailableStock($tenantId, $productId, $warehouseId);
$reservedStock = $totalStock - $availableStock;

echo "Stock Summary:\n";
echo "   Total Stock:     {$totalStock}\n";
echo "   Available Stock: {$availableStock}\n";
echo "   Reserved Stock:  {$reservedStock}\n\n";

// ========================================
// Example 3: Issue Stock for Sales Order
// ========================================

echo "Example 3: Issuing Stock for Sales Order\n";
echo str_repeat('=', 50) . "\n";

$salesOrderNumber = 'SO-2024-005';
$quantityToIssue = 30.0;

try {
    // Check availability first
    if ($availableStock < $quantityToIssue) {
        throw new \Exception("Insufficient stock. Available: {$availableStock}, Requested: {$quantityToIssue}");
    }
    
    $cogs = $stockManager->issueStock(
        tenantId: $tenantId,
        productId: $productId,
        warehouseId: $warehouseId,
        quantity: $quantityToIssue,
        reason: IssueReason::SALE,
        reference: $salesOrderNumber,
        issuedDate: new \DateTimeImmutable('2024-01-20')
    );
    
    echo "✅ Stock issued successfully\n";
    echo "   Product: {$productId}\n";
    echo "   Quantity: {$quantityToIssue}\n";
    echo "   COGS: {$cogs->getAmount()} {$cogs->getCurrency()}\n";
    echo "   Reference: {$salesOrderNumber}\n\n";
    
    // StockIssuedEvent published automatically
    // GL Listener will post: DR COGS / CR Inventory Asset
    
    // Calculate gross profit
    $sellingPrice = Money::of(35.00, 'MYR'); // Example selling price per unit
    $totalRevenue = $sellingPrice->multiply($quantityToIssue);
    $grossProfit = $totalRevenue->subtract($cogs);
    $grossMargin = ($grossProfit->getAmount() / $totalRevenue->getAmount()) * 100;
    
    echo "Profitability Analysis:\n";
    echo "   Revenue: {$totalRevenue->getAmount()} {$totalRevenue->getCurrency()}\n";
    echo "   COGS:    {$cogs->getAmount()} {$cogs->getCurrency()}\n";
    echo "   Gross Profit: {$grossProfit->getAmount()} {$grossProfit->getCurrency()}\n";
    echo "   Gross Margin: " . number_format($grossMargin, 2) . "%\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error issuing stock: {$e->getMessage()}\n\n";
}

// ========================================
// Example 4: Stock Adjustment (Cycle Count)
// ========================================

echo "Example 4: Stock Adjustment after Cycle Count\n";
echo str_repeat('=', 50) . "\n";

use Nexus\Inventory\Enums\AdjustmentReason;

// Suppose physical count shows we have 72 units instead of 70
$physicalCount = 72.0;
$systemCount = $stockManager->getTotalStock($tenantId, $productId, $warehouseId);
$adjustmentQuantity = $physicalCount - $systemCount;

if ($adjustmentQuantity != 0) {
    try {
        $stockManager->adjustStock(
            tenantId: $tenantId,
            productId: $productId,
            warehouseId: $warehouseId,
            adjustmentQuantity: $adjustmentQuantity,
            reason: AdjustmentReason::CYCLE_COUNT,
            notes: "Cycle count variance: Physical {$physicalCount} vs System {$systemCount}",
            adjustmentDate: new \DateTimeImmutable('2024-01-25')
        );
        
        echo "✅ Stock adjusted successfully\n";
        echo "   System Count: {$systemCount}\n";
        echo "   Physical Count: {$physicalCount}\n";
        echo "   Adjustment: " . ($adjustmentQuantity > 0 ? '+' : '') . "{$adjustmentQuantity}\n";
        echo "   Reason: Cycle Count\n\n";
        
        // StockAdjustedEvent published automatically
        // GL Listener will post appropriate variance entry
        
    } catch (\Exception $e) {
        echo "❌ Error adjusting stock: {$e->getMessage()}\n\n";
    }
} else {
    echo "✅ Cycle count matches system. No adjustment needed.\n\n";
}

// ========================================
// Final Stock Summary
// ========================================

echo "Final Stock Summary\n";
echo str_repeat('=', 50) . "\n";

$finalTotal = $stockManager->getTotalStock($tenantId, $productId, $warehouseId);
$finalAvailable = $stockManager->getAvailableStock($tenantId, $productId, $warehouseId);

echo "Product: {$productId}\n";
echo "Warehouse: {$warehouseId}\n";
echo "Total Stock: {$finalTotal}\n";
echo "Available Stock: {$finalAvailable}\n";
echo "Reserved Stock: " . ($finalTotal - $finalAvailable) . "\n";

echo "\n✅ Basic usage examples completed successfully!\n";
