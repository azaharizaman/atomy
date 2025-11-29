<?php

declare(strict_types=1);

/**
 * Basic Usage Examples: Nexus Warehouse
 * 
 * This file demonstrates basic warehouse management operations including:
 * - Creating warehouses
 * - Managing bin locations
 * - Basic pick route optimization
 * - Listing and retrieving warehouse data
 * 
 * Prerequisites:
 * - Laravel or Symfony application with Nexus\Warehouse installed
 * - Database migrations run
 * - Service providers configured
 */

namespace Examples\Warehouse;

use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Contracts\PickingOptimizerInterface;
use Nexus\Warehouse\Exceptions\WarehouseNotFoundException;
use Nexus\Warehouse\Exceptions\BinLocationNotFoundException;
use App\Models\BinLocation;
use Psr\Log\LoggerInterface;

// =============================================================================
// Example 1: Create a New Warehouse
// =============================================================================

function createWarehouse(WarehouseManagerInterface $warehouseManager): string
{
    echo "Example 1: Creating a new warehouse\n";
    echo "====================================\n\n";

    $warehouseId = $warehouseManager->createWarehouse(
        code: 'WH-MAIN',
        name: 'Main Distribution Center',
        metadata: [
            'address' => '123 Industrial Park, Shah Alam, Selangor',
            'capacity' => 50000, // Square feet
            'operational_hours' => '08:00-18:00',
            'contact_phone' => '+60123456789',
            'manager' => 'Ahmad Abdullah',
        ]
    );

    echo "✓ Warehouse created successfully\n";
    echo "  ID: {$warehouseId}\n";
    echo "  Code: WH-MAIN\n";
    echo "  Name: Main Distribution Center\n\n";

    return $warehouseId;
}

// =============================================================================
// Example 2: Retrieve Warehouse Details
// =============================================================================

function getWarehouseDetails(
    WarehouseManagerInterface $warehouseManager,
    string $warehouseId
): void {
    echo "Example 2: Retrieving warehouse details\n";
    echo "========================================\n\n";

    try {
        $warehouse = $warehouseManager->getWarehouse($warehouseId);

        echo "Warehouse Details:\n";
        echo "  ID: {$warehouse['id']}\n";
        echo "  Code: {$warehouse['code']}\n";
        echo "  Name: {$warehouse['name']}\n";
        echo "  Address: {$warehouse['metadata']['address']}\n";
        echo "  Capacity: {$warehouse['metadata']['capacity']} sq ft\n";
        echo "  Active: " . ($warehouse['is_active'] ? 'Yes' : 'No') . "\n\n";
    } catch (WarehouseNotFoundException $e) {
        echo "✗ Error: {$e->getMessage()}\n\n";
    }
}

// =============================================================================
// Example 3: List All Warehouses
// =============================================================================

function listWarehouses(WarehouseManagerInterface $warehouseManager): void
{
    echo "Example 3: Listing all warehouses\n";
    echo "==================================\n\n";

    $warehouses = $warehouseManager->listWarehouses();

    echo "Found " . count($warehouses) . " warehouse(s):\n\n";

    foreach ($warehouses as $warehouse) {
        echo "  • {$warehouse['code']}: {$warehouse['name']}\n";
        echo "    Status: " . ($warehouse['is_active'] ? 'Active' : 'Inactive') . "\n";
        
        if (isset($warehouse['metadata']['address'])) {
            echo "    Address: {$warehouse['metadata']['address']}\n";
        }
        
        echo "\n";
    }
}

// =============================================================================
// Example 4: Create Bin Locations
// =============================================================================

function createBinLocations(string $warehouseId): array
{
    echo "Example 4: Creating bin locations\n";
    echo "==================================\n\n";

    $binIds = [];

    // Create bins in Aisle A, Row 1
    $bins = [
        ['code' => 'A1-01-01', 'lat' => 3.073800, 'lng' => 101.518300],
        ['code' => 'A1-01-02', 'lat' => 3.073820, 'lng' => 101.518320],
        ['code' => 'A1-01-03', 'lat' => 3.073840, 'lng' => 101.518340],
        ['code' => 'A1-02-01', 'lat' => 3.073860, 'lng' => 101.518360],
        ['code' => 'A1-02-02', 'lat' => 3.073880, 'lng' => 101.518380],
        ['code' => 'A1-02-03', 'lat' => 3.073900, 'lng' => 101.518400],
    ];

    foreach ($bins as $bin) {
        $binLocation = BinLocation::create([
            'warehouse_id' => $warehouseId,
            'code' => $bin['code'],
            'latitude' => $bin['lat'],
            'longitude' => $bin['lng'],
            'is_active' => true,
            'metadata' => [
                'zone' => 'Picking',
                'type' => 'Shelf',
            ],
        ]);

        $binIds[$bin['code']] = $binLocation->id;
        
        echo "✓ Created bin: {$bin['code']}\n";
        echo "  Coordinates: {$bin['lat']}, {$bin['lng']}\n";
    }

    echo "\n✓ Created " . count($bins) . " bin locations\n\n";

    return $binIds;
}

// =============================================================================
// Example 5: Basic Pick Route Optimization
// =============================================================================

function optimizeBasicPickRoute(
    PickingOptimizerInterface $pickingOptimizer,
    string $warehouseId,
    array $binIds
): void {
    echo "Example 5: Basic pick route optimization\n";
    echo "=========================================\n\n";

    // Create a simple pick list
    $pickItems = [
        [
            'bin_id' => $binIds['A1-02-03'],
            'product_id' => 'PROD-001',
            'quantity' => 5.0,
        ],
        [
            'bin_id' => $binIds['A1-01-01'],
            'product_id' => 'PROD-002',
            'quantity' => 2.0,
        ],
        [
            'bin_id' => $binIds['A1-02-01'],
            'product_id' => 'PROD-003',
            'quantity' => 10.0,
        ],
        [
            'bin_id' => $binIds['A1-01-03'],
            'product_id' => 'PROD-004',
            'quantity' => 3.0,
        ],
    ];

    echo "Original pick list (sequential order):\n";
    echo "  1. A1-02-03 - PROD-001 - Qty: 5.0\n";
    echo "  2. A1-01-01 - PROD-002 - Qty: 2.0\n";
    echo "  3. A1-02-01 - PROD-003 - Qty: 10.0\n";
    echo "  4. A1-01-03 - PROD-004 - Qty: 3.0\n\n";

    try {
        // Optimize the route
        $result = $pickingOptimizer->optimizePickRoute($warehouseId, $pickItems);

        echo "Optimized pick route:\n";
        $sequence = $result->getOptimizedSequence();
        
        foreach ($sequence as $index => $pick) {
            $num = $index + 1;
            echo "  {$num}. {$pick['bin_code']} - {$pick['product_id']} - Qty: {$pick['quantity']}\n";
        }

        echo "\nOptimization Metrics:\n";
        echo "  Total Distance: " . round($result->getTotalDistance(), 2) . " meters\n";
        echo "  Distance Improvement: " . round($result->getDistanceImprovement(), 2) . "%\n";
        echo "  Execution Time: {$result->getExecutionTime()} ms\n\n";

        if ($result->getDistanceImprovement() > 0) {
            echo "✓ Route optimized successfully - " 
                . round($result->getDistanceImprovement(), 2) 
                . "% shorter than sequential picking\n\n";
        }
    } catch (BinLocationNotFoundException $e) {
        echo "✗ Error: {$e->getMessage()}\n\n";
    }
}

// =============================================================================
// Example 6: Handling Missing Coordinates
// =============================================================================

function handleMissingCoordinates(
    PickingOptimizerInterface $pickingOptimizer,
    string $warehouseId
): void {
    echo "Example 6: Handling bins without GPS coordinates\n";
    echo "=================================================\n\n";

    // Create bin without coordinates
    $binWithoutCoords = BinLocation::create([
        'warehouse_id' => $warehouseId,
        'code' => 'STORAGE-001',
        'latitude' => null, // No coordinates
        'longitude' => null,
        'is_active' => true,
        'metadata' => ['zone' => 'Storage'],
    ]);

    echo "Created bin without coordinates: STORAGE-001\n\n";

    // Create bin with coordinates
    $binWithCoords = BinLocation::create([
        'warehouse_id' => $warehouseId,
        'code' => 'A1-03-01',
        'latitude' => 3.073920,
        'longitude' => 101.518420,
        'is_active' => true,
    ]);

    $pickItems = [
        [
            'bin_id' => $binWithCoords->id,
            'product_id' => 'PROD-010',
            'quantity' => 5.0,
        ],
        [
            'bin_id' => $binWithoutCoords->id,
            'product_id' => 'PROD-011',
            'quantity' => 2.0,
        ],
    ];

    $result = $pickingOptimizer->optimizePickRoute($warehouseId, $pickItems);

    echo "Optimization result:\n";
    foreach ($result->getOptimizedSequence() as $index => $pick) {
        $num = $index + 1;
        echo "  {$num}. {$pick['bin_code']} - {$pick['product_id']}\n";
    }

    echo "\nNote: Bins without coordinates are placed at the end in original order.\n";
    echo "Distance Improvement: " . round($result->getDistanceImprovement(), 2) . "%\n\n";

    // Clean up
    $binWithoutCoords->delete();
    $binWithCoords->delete();
}

// =============================================================================
// Example 7: Exception Handling
// =============================================================================

function demonstrateExceptionHandling(
    WarehouseManagerInterface $warehouseManager,
    PickingOptimizerInterface $pickingOptimizer
): void {
    echo "Example 7: Exception handling\n";
    echo "==============================\n\n";

    // 1. Handle WarehouseNotFoundException
    echo "1. Handling WarehouseNotFoundException:\n";
    try {
        $warehouse = $warehouseManager->getWarehouse('invalid-warehouse-id');
        echo "   Warehouse found: {$warehouse['name']}\n";
    } catch (WarehouseNotFoundException $e) {
        echo "   ✓ Caught expected exception: {$e->getMessage()}\n";
    }

    echo "\n";

    // 2. Handle BinLocationNotFoundException
    echo "2. Handling BinLocationNotFoundException:\n";
    try {
        $result = $pickingOptimizer->optimizePickRoute('wh-123', [
            ['bin_id' => 'invalid-bin-id', 'product_id' => 'PROD-999', 'quantity' => 1.0],
        ]);
        echo "   Route optimized\n";
    } catch (BinLocationNotFoundException $e) {
        echo "   ✓ Caught expected exception: {$e->getMessage()}\n";
    }

    echo "\n";
}

// =============================================================================
// Main Execution
// =============================================================================

/**
 * Run all basic usage examples
 * 
 * This function demonstrates the complete workflow of warehouse management.
 */
function runBasicExamples(
    WarehouseManagerInterface $warehouseManager,
    PickingOptimizerInterface $pickingOptimizer,
    LoggerInterface $logger
): void {
    $logger->info('Starting Warehouse basic usage examples');

    try {
        // Example 1: Create warehouse
        $warehouseId = createWarehouse($warehouseManager);

        // Example 2: Get warehouse details
        getWarehouseDetails($warehouseManager, $warehouseId);

        // Example 3: List all warehouses
        listWarehouses($warehouseManager);

        // Example 4: Create bin locations
        $binIds = createBinLocations($warehouseId);

        // Example 5: Optimize pick route
        optimizeBasicPickRoute($pickingOptimizer, $warehouseId, $binIds);

        // Example 6: Handle missing coordinates
        handleMissingCoordinates($pickingOptimizer, $warehouseId);

        // Example 7: Exception handling
        demonstrateExceptionHandling($warehouseManager, $pickingOptimizer);

        echo "✓ All basic examples completed successfully\n";
        
        $logger->info('All Warehouse basic usage examples completed successfully');
    } catch (\Exception $e) {
        echo "✗ Error: {$e->getMessage()}\n";
        $logger->error('Warehouse basic usage example failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

// =============================================================================
// Laravel Usage Example
// =============================================================================

/**
 * Example usage in Laravel application
 */
function laravelExample(): void
{
    // In Laravel controller or command
    $warehouseManager = app(WarehouseManagerInterface::class);
    $pickingOptimizer = app(PickingOptimizerInterface::class);
    $logger = app(LoggerInterface::class);

    runBasicExamples($warehouseManager, $pickingOptimizer, $logger);
}

// =============================================================================
// Symfony Usage Example
// =============================================================================

/**
 * Example usage in Symfony application
 */
function symfonyExample(
    WarehouseManagerInterface $warehouseManager,
    PickingOptimizerInterface $pickingOptimizer,
    LoggerInterface $logger
): void {
    // In Symfony controller or command (services auto-wired)
    runBasicExamples($warehouseManager, $pickingOptimizer, $logger);
}
