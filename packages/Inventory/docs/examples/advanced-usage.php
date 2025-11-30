<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Nexus Inventory Package
 * 
 * This example demonstrates advanced features:
 * - Lot tracking with FEFO (First-Expiry-First-Out)
 * - Serial number management
 * - Stock reservations with TTL
 * - Inter-warehouse transfers with FSM
 */

use Nexus\Inventory\Contracts\{
    StockManagerInterface,
    LotManagerInterface,
    SerialNumberManagerInterface,
    ReservationManagerInterface,
    TransferManagerInterface
};
use Nexus\Inventory\Enums\{IssueReason, ReleaseReason, TransferStatus};
use Nexus\Currency\ValueObjects\Money;

// Assume dependency injection container provides these
/** @var StockManagerInterface $stockManager */
$stockManager = $container->get(StockManagerInterface::class);
/** @var LotManagerInterface $lotManager */
$lotManager = $container->get(LotManagerInterface::class);
/** @var SerialNumberManagerInterface $serialManager */
$serialManager = $container->get(SerialNumberManagerInterface::class);
/** @var ReservationManagerInterface $reservationManager */
$reservationManager = $container->get(ReservationManagerInterface::class);
/** @var TransferManagerInterface $transferManager */
$transferManager = $container->get(TransferManagerInterface::class);

$tenantId = 'tenant-abc123';

// ========================================
// Example 1: Lot Tracking with FEFO
// ========================================

echo "Example 1: Lot Tracking with FEFO (First-Expiry-First-Out)\n";
echo str_repeat('=', 60) . "\n";

$productId = 'product-milk'; // Perishable product
$warehouseId = 'warehouse-main';

// Receive multiple lots with different expiry dates
$lots = [
    ['lot' => 'LOT-2024-003', 'qty' => 50, 'expiry' => '2024-02-15', 'cost' => 15.00],
    ['lot' => 'LOT-2024-001', 'qty' => 40, 'expiry' => '2024-02-01', 'cost' => 14.50], // Expires first
    ['lot' => 'LOT-2024-002', 'qty' => 60, 'expiry' => '2024-02-10', 'cost' => 14.80],
];

echo "Receiving stock in multiple lots:\n";
foreach ($lots as $lot) {
    try {
        $lotId = $lotManager->createLot(
            tenantId: $tenantId,
            productId: $productId,
            lotNumber: $lot['lot'],
            quantity: $lot['qty'],
            expiryDate: new \DateTimeImmutable($lot['expiry'])
        );
        
        $stockManager->receiveStock(
            tenantId: $tenantId,
            productId: $productId,
            warehouseId: $warehouseId,
            quantity: $lot['qty'],
            unitCost: Money::of($lot['cost'], 'MYR'),
            lotNumber: $lot['lot'],
            expiryDate: new \DateTimeImmutable($lot['expiry'])
        );
        
        echo "   ✅ {$lot['lot']}: {$lot['qty']} units, expires {$lot['expiry']}\n";
    } catch (\Exception $e) {
        echo "   ❌ Error: {$e->getMessage()}\n";
    }
}

// Check for expiring lots (within 30 days)
echo "\nChecking for expiring lots (30-day threshold):\n";
$expiringLots = $lotManager->getExpiringLots($tenantId, daysThreshold: 30);
foreach ($expiringLots as $lot) {
    $daysUntilExpiry = (new \DateTime())->diff(new \DateTime($lot->getExpiryDate()->format('Y-m-d')))->days;
    echo "   ⚠️  {$lot->getLotNumber()}: {$lot->getQuantityRemaining()} units, expires in {$daysUntilExpiry} days\n";
}

// Issue stock using FEFO (system automatically picks from oldest expiring lots)
echo "\nIssuing 80 units using FEFO allocation:\n";
try {
    $allocations = $lotManager->allocateFromLots($tenantId, $productId, quantity: 80.0);
    
    echo "FEFO Allocation:\n";
    foreach ($allocations as $allocation) {
        echo "   📦 {$allocation->lotNumber}: {$allocation->quantityAllocated} units";
        if ($allocation->expiryDate) {
            echo " (expires {$allocation->expiryDate->format('Y-m-d')})";
        }
        echo "\n";
    }
    
    // Expected allocation:
    // LOT-2024-001: 40 units (expires 2024-02-01) ← Oldest expiry
    // LOT-2024-002: 40 units (expires 2024-02-10) ← Next oldest
    
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";
}

// ========================================
// Example 2: Serial Number Management
// ========================================

echo "\n\nExample 2: Serial Number Tracking (High-Value Items)\n";
echo str_repeat('=', 60) . "\n";

$productIdSerial = 'product-laptop'; // Serialized product
$serialNumbers = ['SN-LP-00123', 'SN-LP-00124', 'SN-LP-00125'];

echo "Registering serial numbers:\n";
foreach ($serialNumbers as $serial) {
    try {
        $serialId = $serialManager->registerSerial(
            tenantId: $tenantId,
            productId: $productIdSerial,
            serialNumber: $serial,
            manufactureDate: new \DateTimeImmutable('2024-01-10')
        );
        
        echo "   ✅ Registered: {$serial}\n";
    } catch (\Exception $e) {
        echo "   ❌ Error: {$e->getMessage()}\n";
    }
}

// Check serial availability
echo "\nChecking serial availability:\n";
foreach ($serialNumbers as $serial) {
    $isAvailable = $serialManager->isAvailable($tenantId, $serial);
    $status = $isAvailable ? '✅ Available' : '❌ Not Available';
    echo "   {$serial}: {$status}\n";
}

// Issue a specific serial (sold to customer)
echo "\nIssuing serial SN-LP-00123 for sales order:\n";
try {
    $serialManager->issueSerial(
        tenantId: $tenantId,
        serialNumber: 'SN-LP-00123',
        reference: 'SO-2024-010',
        issuedDate: new \DateTimeImmutable('2024-01-20')
    );
    
    echo "   ✅ Serial SN-LP-00123 issued successfully\n";
    
    // Get serial history
    $history = $serialManager->getHistory($tenantId, 'SN-LP-00123');
    echo "   History:\n";
    foreach ($history as $event) {
        echo "      - {$event['event']}: {$event['date']->format('Y-m-d')} (Ref: {$event['reference']})\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";
}

// ========================================
// Example 3: Stock Reservations with TTL
// ========================================

echo "\n\nExample 3: Stock Reservations with Auto-Expiry\n";
echo str_repeat('=', 60) . "\n";

$productIdReserve = 'product-widget';
$warehouseIdReserve = 'warehouse-main';

// Assume we have 100 units in stock
echo "Creating reservation for sales order (48-hour TTL):\n";
try {
    $reservationId = $reservationManager->reserve(
        tenantId: $tenantId,
        productId: $productIdReserve,
        warehouseId: $warehouseIdReserve,
        quantity: 25.0,
        referenceType: 'SALES_ORDER',
        referenceId: 'SO-2024-015',
        ttlHours: 48 // Auto-expire in 48 hours if not fulfilled
    );
    
    echo "   ✅ Reservation created: {$reservationId}\n";
    echo "   Reserved: 25 units for SO-2024-015\n";
    echo "   TTL: 48 hours\n\n";
    
    // Check stock availability (should show 25 units reserved)
    $totalStock = $stockManager->getTotalStock($tenantId, $productIdReserve, $warehouseIdReserve);
    $availableStock = $stockManager->getAvailableStock($tenantId, $productIdReserve, $warehouseIdReserve);
    
    echo "Stock Status:\n";
    echo "   Total: {$totalStock} units\n";
    echo "   Available: {$availableStock} units (accounting for reservations)\n";
    echo "   Reserved: " . ($totalStock - $availableStock) . " units\n\n";
    
    // Scenario 1: Order fulfilled (release reservation)
    echo "Scenario 1: Order fulfilled, releasing reservation:\n";
    $reservationManager->release($reservationId, ReleaseReason::FULFILLED);
    echo "   ✅ Reservation released (reason: FULFILLED)\n\n";
    
    // Scenario 2: Order cancelled
    $reservationId2 = $reservationManager->reserve(
        tenantId: $tenantId,
        productId: $productIdReserve,
        warehouseId: $warehouseIdReserve,
        quantity: 15.0,
        referenceType: 'SALES_ORDER',
        referenceId: 'SO-2024-016',
        ttlHours: 48
    );
    
    echo "Scenario 2: Order cancelled, releasing reservation:\n";
    $reservationManager->release($reservationId2, ReleaseReason::CANCELLED);
    echo "   ✅ Reservation released (reason: CANCELLED)\n\n";
    
    // Scenario 3: Auto-expiry (TTL exceeded)
    echo "Scenario 3: Simulating auto-expiry for stale reservations:\n";
    $expiredCount = $reservationManager->expireReservations($tenantId);
    echo "   ✅ {$expiredCount} reservation(s) expired (TTL exceeded)\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";
}

// ========================================
// Example 4: Inter-Warehouse Transfer with FSM
// ========================================

echo "\n\nExample 4: Inter-Warehouse Stock Transfer (FSM Workflow)\n";
echo str_repeat('=', 60) . "\n";

$productIdTransfer = 'product-gadget';
$fromWarehouse = 'warehouse-main';
$toWarehouse = 'warehouse-branch';

echo "Initiating stock transfer:\n";
echo "   From: {$fromWarehouse}\n";
echo "   To: {$toWarehouse}\n";
echo "   Product: {$productIdTransfer}\n";
echo "   Quantity: 50 units\n\n";

try {
    // Step 1: Initiate transfer (pending state)
    $transferId = $transferManager->initiateTransfer(
        tenantId: $tenantId,
        productId: $productIdTransfer,
        fromWarehouseId: $fromWarehouse,
        toWarehouseId: $toWarehouse,
        quantity: 50.0,
        reason: 'REBALANCING',
        requestedDate: new \DateTimeImmutable('2024-02-01')
    );
    
    echo "✅ Step 1: Transfer initiated (Status: PENDING)\n";
    echo "   Transfer ID: {$transferId}\n\n";
    
    $status = $transferManager->getStatus($transferId);
    echo "   Current Status: {$status->value}\n\n";
    
    // Step 2: Start shipment (pending → in_transit)
    sleep(1); // Simulate delay
    $transferManager->startShipment(
        transferId: $transferId,
        shippedDate: new \DateTimeImmutable('2024-02-02'),
        trackingNumber: 'TRK-ABC-12345'
    );
    
    echo "✅ Step 2: Shipment started (Status: IN_TRANSIT)\n";
    echo "   Tracking: TRK-ABC-12345\n";
    echo "   Stock reserved at source warehouse\n\n";
    
    $status = $transferManager->getStatus($transferId);
    echo "   Current Status: {$status->value}\n\n";
    
    // Step 3: Complete transfer (in_transit → completed)
    sleep(1); // Simulate delay
    $transferManager->completeTransfer(
        transferId: $transferId,
        receivedDate: new \DateTimeImmutable('2024-02-05')
    );
    
    echo "✅ Step 3: Transfer completed (Status: COMPLETED)\n";
    echo "   Stock decremented at source warehouse\n";
    echo "   Stock incremented at destination warehouse\n\n";
    
    $status = $transferManager->getStatus($transferId);
    echo "   Final Status: {$status->value}\n\n";
    
    // FSM State Transitions:
    // pending → in_transit → completed ✅
    
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n\n";
}

// Example 4b: Cancel transfer scenario
echo "Example 4b: Cancel Transfer (any state → cancelled)\n";
echo str_repeat('-', 60) . "\n";

try {
    $transferId2 = $transferManager->initiateTransfer(
        tenantId: $tenantId,
        productId: $productIdTransfer,
        fromWarehouseId: $fromWarehouse,
        toWarehouseId: $toWarehouse,
        quantity: 30.0,
        reason: 'DEMAND'
    );
    
    echo "✅ Transfer initiated: {$transferId2}\n";
    
    // Cancel before shipment
    $transferManager->cancelTransfer($transferId2, cancellationReason: 'Demand no longer exists');
    
    echo "✅ Transfer cancelled (Status: CANCELLED)\n";
    echo "   Stock released back to source warehouse\n\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";
}

// ========================================
// Example 5: Get Active Reservations
// ========================================

echo "\nExample 5: Querying Active Reservations\n";
echo str_repeat('=', 60) . "\n";

$activeReservations = $reservationManager->getActiveReservations(
    tenantId: $tenantId,
    productId: $productIdReserve,
    warehouseId: $warehouseIdReserve
);

echo "Active reservations for {$productIdReserve} in {$warehouseIdReserve}:\n";
if (count($activeReservations) === 0) {
    echo "   No active reservations\n";
} else {
    foreach ($activeReservations as $reservation) {
        echo "   📌 {$reservation->getId()}: {$reservation->getQuantity()} units\n";
        echo "      Reference: {$reservation->getReferenceType()} - {$reservation->getReferenceId()}\n";
        echo "      Expires: {$reservation->getExpiresAt()->format('Y-m-d H:i:s')}\n";
    }
}

echo "\n✅ Advanced usage examples completed successfully!\n";
echo "\nKey Features Demonstrated:\n";
echo "   1. ✅ FEFO (First-Expiry-First-Out) lot allocation\n";
echo "   2. ✅ Serial number registration and tracking\n";
echo "   3. ✅ Stock reservations with auto-expiry (TTL)\n";
echo "   4. ✅ FSM-based inter-warehouse transfers\n";
echo "   5. ✅ Expiring lot detection\n";
echo "   6. ✅ Reservation queries and management\n";
