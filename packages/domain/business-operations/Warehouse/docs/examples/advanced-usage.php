<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples: Nexus Warehouse
 * 
 * This file demonstrates advanced warehouse management scenarios including:
 * - Multi-warehouse product allocation
 * - Large-scale pick route optimization
 * - Batch bin creation with coordinate calculation
 * - Performance monitoring and optimization
 * - Integration with Nexus\Inventory and Nexus\AuditLogger
 * - Wave picking strategies
 * - Zone-based picking optimization
 * 
 * Prerequisites:
 * - Laravel or Symfony application with Nexus\Warehouse installed
 * - Nexus\Inventory, Nexus\AuditLogger, Nexus\Monitoring packages installed
 * - Large warehouse dataset (1000+ bins)
 */

namespace Examples\Warehouse\Advanced;

use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Contracts\PickingOptimizerInterface;
use Nexus\Warehouse\Exceptions\BinLocationNotFoundException;
use App\Models\Warehouse;
use App\Models\BinLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Ulid;

// =============================================================================
// Example 1: Batch Bin Creation with Calculated Coordinates
// =============================================================================

/**
 * Create a large number of bins with automatically calculated GPS coordinates
 * 
 * This example simulates a 50-aisle warehouse with calculated coordinates
 * based on warehouse layout dimensions.
 */
function createWarehouseLayout(string $warehouseId): array
{
    echo "Example 1: Creating large warehouse layout\n";
    echo "===========================================\n\n";

    // Warehouse physical layout parameters
    $startLat = 3.073800;
    $startLng = 101.518300;
    $aisleCount = 50;
    $rowsPerAisle = 20;
    $shelvesPerRow = 5;
    
    // Distance between positions (in degrees, approximately 10 meters)
    $aisleSeparation = 0.0001; // ~10 meters between aisles
    $rowSeparation = 0.00005;  // ~5 meters between rows
    $shelfSeparation = 0.00002; // ~2 meters between shelves

    $bins = [];
    $binCount = 0;

    echo "Creating layout:\n";
    echo "  Aisles: {$aisleCount}\n";
    echo "  Rows per aisle: {$rowsPerAisle}\n";
    echo "  Shelves per row: {$shelvesPerRow}\n";
    echo "  Total bins: " . ($aisleCount * $rowsPerAisle * $shelvesPerRow) . "\n\n";

    $startTime = microtime(true);

    for ($aisle = 1; $aisle <= $aisleCount; $aisle++) {
        for ($row = 1; $row <= $rowsPerAisle; $row++) {
            for ($shelf = 1; $shelf <= $shelvesPerRow; $shelf++) {
                $binCode = sprintf('A%d-%02d-%02d', $aisle, $row, $shelf);
                
                // Calculate coordinates based on position
                $latitude = $startLat + ($aisle * $aisleSeparation);
                $longitude = $startLng + ($row * $rowSeparation) + ($shelf * $shelfSeparation);

                $bins[] = [
                    'id' => (string) new Ulid(),
                    'warehouse_id' => $warehouseId,
                    'code' => $binCode,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'is_active' => true,
                    'metadata' => json_encode([
                        'zone' => $shelf <= 2 ? 'Picking' : 'Storage',
                        'aisle' => $aisle,
                        'row' => $row,
                        'shelf' => $shelf,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $binCount++;

                // Batch insert every 1000 records
                if (count($bins) >= 1000) {
                    BinLocation::insert($bins);
                    echo "  ✓ Inserted {$binCount} bins...\n";
                    $bins = [];
                }
            }
        }
    }

    // Insert remaining bins
    if (count($bins) > 0) {
        BinLocation::insert($bins);
    }

    $duration = round((microtime(true) - $startTime) * 1000, 2);

    echo "\n✓ Created {$binCount} bin locations in {$duration} ms\n";
    echo "  Average: " . round($binCount / ($duration / 1000), 0) . " bins/second\n\n";

    return compact('binCount', 'duration');
}

// =============================================================================
// Example 2: Wave Picking Optimization
// =============================================================================

/**
 * Optimize multiple pick waves for parallel picker assignment
 * 
 * Wave picking allows multiple pickers to work simultaneously on different
 * sections of the warehouse.
 */
function optimizeWavePicking(
    PickingOptimizerInterface $pickingOptimizer,
    string $warehouseId,
    array $orders
): array {
    echo "Example 2: Wave picking optimization\n";
    echo "====================================\n\n";

    // Group orders into waves (e.g., by shipping zone or priority)
    $waves = [
        'Wave 1 - Express' => [],
        'Wave 2 - Standard' => [],
        'Wave 3 - Bulk' => [],
    ];

    // Simulate order categorization
    foreach ($orders as $index => $order) {
        if ($index % 3 === 0) {
            $waves['Wave 1 - Express'][] = $order;
        } elseif ($index % 3 === 1) {
            $waves['Wave 2 - Standard'][] = $order;
        } else {
            $waves['Wave 3 - Bulk'][] = $order;
        }
    }

    $waveResults = [];

    foreach ($waves as $waveName => $waveOrders) {
        if (empty($waveOrders)) {
            continue;
        }

        echo "Processing {$waveName}:\n";
        echo "  Orders in wave: " . count($waveOrders) . "\n";

        // Consolidate all pick items from orders in this wave
        $pickItems = [];
        foreach ($waveOrders as $order) {
            $pickItems = array_merge($pickItems, $order['items']);
        }

        echo "  Total picks: " . count($pickItems) . "\n";

        // Optimize the wave
        $startTime = microtime(true);
        $result = $pickingOptimizer->optimizePickRoute($warehouseId, $pickItems);
        $duration = (microtime(true) - $startTime) * 1000;

        echo "  Optimized distance: " . round($result->getTotalDistance(), 2) . " meters\n";
        echo "  Improvement: " . round($result->getDistanceImprovement(), 2) . "%\n";
        echo "  Optimization time: " . round($duration, 2) . " ms\n\n";

        $waveResults[$waveName] = [
            'order_count' => count($waveOrders),
            'pick_count' => count($pickItems),
            'distance' => $result->getTotalDistance(),
            'improvement' => $result->getDistanceImprovement(),
            'sequence' => $result->getOptimizedSequence(),
        ];
    }

    return $waveResults;
}

// =============================================================================
// Example 3: Zone-Based Picking Strategy
// =============================================================================

/**
 * Optimize picks by warehouse zone to minimize cross-zone travel
 */
function optimizeByZone(
    PickingOptimizerInterface $pickingOptimizer,
    string $warehouseId,
    array $pickItems
): array {
    echo "Example 3: Zone-based picking optimization\n";
    echo "==========================================\n\n";

    // Group picks by zone
    $zoneGroups = [];
    
    foreach ($pickItems as $item) {
        $bin = BinLocation::find($item['bin_id']);
        $zone = $bin->metadata['zone'] ?? 'Unknown';
        
        if (!isset($zoneGroups[$zone])) {
            $zoneGroups[$zone] = [];
        }
        
        $zoneGroups[$zone][] = $item;
    }

    echo "Pick items grouped into " . count($zoneGroups) . " zones:\n";
    foreach ($zoneGroups as $zone => $items) {
        echo "  {$zone}: " . count($items) . " picks\n";
    }
    echo "\n";

    // Optimize each zone separately
    $zoneResults = [];
    $totalDistance = 0;
    $totalImprovement = 0;

    foreach ($zoneGroups as $zone => $items) {
        echo "Optimizing zone: {$zone}\n";
        
        $result = $pickingOptimizer->optimizePickRoute($warehouseId, $items);
        
        echo "  Distance: " . round($result->getTotalDistance(), 2) . " meters\n";
        echo "  Improvement: " . round($result->getDistanceImprovement(), 2) . "%\n\n";

        $zoneResults[$zone] = [
            'picks' => count($items),
            'distance' => $result->getTotalDistance(),
            'improvement' => $result->getDistanceImprovement(),
            'sequence' => $result->getOptimizedSequence(),
        ];

        $totalDistance += $result->getTotalDistance();
        $totalImprovement += $result->getDistanceImprovement();
    }

    echo "Overall Results:\n";
    echo "  Total Distance: " . round($totalDistance, 2) . " meters\n";
    echo "  Average Improvement: " . round($totalImprovement / count($zoneGroups), 2) . "%\n\n";

    return $zoneResults;
}

// =============================================================================
// Example 4: Multi-Warehouse Product Allocation
// =============================================================================

/**
 * Allocate order fulfillment across multiple warehouses to minimize shipping costs
 */
function optimizeMultiWarehouseAllocation(
    WarehouseManagerInterface $warehouseManager,
    array $orderItems,
    string $deliveryPostalCode
): array {
    echo "Example 4: Multi-warehouse product allocation\n";
    echo "==============================================\n\n";

    $warehouses = $warehouseManager->listWarehouses();
    
    echo "Available warehouses: " . count($warehouses) . "\n\n";

    // Calculate inventory availability per warehouse
    $allocationPlan = [];

    foreach ($orderItems as $item) {
        echo "Finding stock for Product {$item['product_id']} (Qty: {$item['quantity']}):\n";
        
        $allocated = 0;
        $allocationPlan[$item['product_id']] = [];

        foreach ($warehouses as $warehouse) {
            // Query inventory in this warehouse
            $inventory = DB::table('inventory_items')
                ->where('warehouse_id', $warehouse['id'])
                ->where('product_id', $item['product_id'])
                ->where('quantity', '>', 0)
                ->sum('quantity');

            if ($inventory > 0) {
                $allocateQty = min($inventory, $item['quantity'] - $allocated);
                
                $allocationPlan[$item['product_id']][] = [
                    'warehouse_id' => $warehouse['id'],
                    'warehouse_code' => $warehouse['code'],
                    'quantity' => $allocateQty,
                ];

                echo "  ✓ {$warehouse['code']}: {$allocateQty} units\n";

                $allocated += $allocateQty;

                if ($allocated >= $item['quantity']) {
                    break;
                }
            }
        }

        if ($allocated < $item['quantity']) {
            $shortfall = $item['quantity'] - $allocated;
            echo "  ✗ Shortfall: {$shortfall} units\n";
        }

        echo "\n";
    }

    return $allocationPlan;
}

// =============================================================================
// Example 5: Performance Monitoring and Caching
// =============================================================================

/**
 * Monitor picking performance and implement caching strategies
 */
function monitorPickingPerformance(
    PickingOptimizerInterface $pickingOptimizer,
    string $warehouseId,
    array $pickItems,
    LoggerInterface $logger
): array {
    echo "Example 5: Performance monitoring and caching\n";
    echo "=============================================\n\n";

    // Cache key for bin coordinates
    $cacheKey = "warehouse:{$warehouseId}:bin_coordinates";

    // Check cache
    $cachedCoordinates = Cache::get($cacheKey);

    if ($cachedCoordinates === null) {
        echo "Loading bin coordinates from database...\n";
        
        $startTime = microtime(true);
        
        $coordinates = DB::table('bin_locations')
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('id', 'code', 'latitude', 'longitude')
            ->get()
            ->keyBy('id')
            ->toArray();

        $loadDuration = (microtime(true) - $startTime) * 1000;
        
        echo "✓ Loaded " . count($coordinates) . " coordinates in " . round($loadDuration, 2) . " ms\n";
        
        // Cache for 1 hour
        Cache::put($cacheKey, $coordinates, 3600);
        
        echo "✓ Cached coordinates for future use\n\n";
    } else {
        echo "✓ Using cached bin coordinates (" . count($cachedCoordinates) . " bins)\n\n";
    }

    // Optimize route with performance tracking
    echo "Optimizing pick route...\n";
    
    $metrics = [];
    $metrics['start_time'] = microtime(true);
    $metrics['pick_count'] = count($pickItems);

    try {
        $result = $pickingOptimizer->optimizePickRoute($warehouseId, $pickItems);

        $metrics['end_time'] = microtime(true);
        $metrics['duration_ms'] = ($metrics['end_time'] - $metrics['start_time']) * 1000;
        $metrics['distance'] = $result->getTotalDistance();
        $metrics['improvement'] = $result->getDistanceImprovement();
        $metrics['optimizer_time_ms'] = $result->getExecutionTime();

        echo "Performance Metrics:\n";
        echo "  Pick Count: {$metrics['pick_count']}\n";
        echo "  Total Duration: " . round($metrics['duration_ms'], 2) . " ms\n";
        echo "  Optimizer Time: {$metrics['optimizer_time_ms']} ms\n";
        echo "  Distance: " . round($metrics['distance'], 2) . " meters\n";
        echo "  Improvement: " . round($metrics['improvement'], 2) . "%\n\n";

        // Log metrics
        $logger->info('Pick route optimization completed', $metrics);

        // Check performance thresholds
        if ($metrics['duration_ms'] > 1000) {
            echo "⚠ Warning: Optimization took longer than 1 second\n";
            echo "  Consider breaking into smaller batches\n\n";
            
            $logger->warning('Slow pick optimization', [
                'duration_ms' => $metrics['duration_ms'],
                'pick_count' => $metrics['pick_count'],
            ]);
        }

        return $metrics;
    } catch (\Exception $e) {
        $logger->error('Pick optimization failed', [
            'error' => $e->getMessage(),
            'pick_count' => count($pickItems),
        ]);
        
        throw $e;
    }
}

// =============================================================================
// Example 6: Batched Optimization for Large Pick Lists
// =============================================================================

/**
 * Handle large pick lists (100+ items) by batching
 */
function optimizeLargePickList(
    PickingOptimizerInterface $pickingOptimizer,
    string $warehouseId,
    array $pickItems
): array {
    echo "Example 6: Batched optimization for large pick lists\n";
    echo "=====================================================\n\n";

    $totalItems = count($pickItems);
    $batchSize = 50; // Optimal batch size for TSP performance
    $batches = array_chunk($pickItems, $batchSize);

    echo "Total picks: {$totalItems}\n";
    echo "Batch size: {$batchSize}\n";
    echo "Number of batches: " . count($batches) . "\n\n";

    $allResults = [];
    $totalDistance = 0;
    $totalTime = 0;

    foreach ($batches as $batchIndex => $batch) {
        $batchNum = $batchIndex + 1;
        echo "Optimizing batch {$batchNum}/" . count($batches) . " (" . count($batch) . " items)...\n";

        $startTime = microtime(true);
        $result = $pickingOptimizer->optimizePickRoute($warehouseId, $batch);
        $duration = (microtime(true) - $startTime) * 1000;

        echo "  Distance: " . round($result->getTotalDistance(), 2) . " meters\n";
        echo "  Improvement: " . round($result->getDistanceImprovement(), 2) . "%\n";
        echo "  Time: " . round($duration, 2) . " ms\n\n";

        $totalDistance += $result->getTotalDistance();
        $totalTime += $duration;

        $allResults[] = [
            'batch' => $batchNum,
            'items' => count($batch),
            'distance' => $result->getTotalDistance(),
            'improvement' => $result->getDistanceImprovement(),
            'sequence' => $result->getOptimizedSequence(),
        ];
    }

    echo "Overall Results:\n";
    echo "  Total Distance: " . round($totalDistance, 2) . " meters\n";
    echo "  Total Time: " . round($totalTime, 2) . " ms\n";
    echo "  Average per batch: " . round($totalTime / count($batches), 2) . " ms\n\n";

    return $allResults;
}

// =============================================================================
// Example 7: Integration with Audit Logger
// =============================================================================

/**
 * Track warehouse operations with audit logging
 */
function auditWarehouseOperations(
    WarehouseManagerInterface $warehouseManager,
    PickingOptimizerInterface $pickingOptimizer,
    $auditLogger, // Nexus\AuditLogger\Contracts\AuditLoggerInterface
    string $userId
): void {
    echo "Example 7: Audit logging integration\n";
    echo "=====================================\n\n";

    // Create warehouse with audit trail
    echo "Creating warehouse with audit trail...\n";
    
    $warehouseId = $warehouseManager->createWarehouse(
        code: 'WH-AUDIT-01',
        name: 'Audited Warehouse',
        metadata: ['purpose' => 'audit_demo']
    );

    $auditLogger->log(
        entityType: 'warehouse',
        entityId: $warehouseId,
        action: 'created',
        userId: $userId,
        metadata: [
            'code' => 'WH-AUDIT-01',
            'name' => 'Audited Warehouse',
        ]
    );

    echo "✓ Warehouse created and logged\n\n";

    // Simulate picking operation with audit
    echo "Performing pick operation with audit trail...\n";

    $pickItems = [
        ['bin_id' => 'bin-1', 'product_id' => 'PROD-A', 'quantity' => 5.0],
        ['bin_id' => 'bin-2', 'product_id' => 'PROD-B', 'quantity' => 3.0],
    ];

    $startTime = microtime(true);
    
    try {
        $result = $pickingOptimizer->optimizePickRoute($warehouseId, $pickItems);
        $duration = (microtime(true) - $startTime) * 1000;

        $auditLogger->log(
            entityType: 'pick_route',
            entityId: uniqid('pick_'),
            action: 'optimized',
            userId: $userId,
            metadata: [
                'warehouse_id' => $warehouseId,
                'item_count' => count($pickItems),
                'distance_meters' => $result->getTotalDistance(),
                'improvement_percent' => $result->getDistanceImprovement(),
                'duration_ms' => round($duration, 2),
            ]
        );

        echo "✓ Pick route optimized and logged\n";
        echo "  Distance: " . round($result->getTotalDistance(), 2) . " meters\n";
        echo "  Logged to audit trail\n\n";
    } catch (BinLocationNotFoundException $e) {
        $auditLogger->log(
            entityType: 'pick_route',
            entityId: uniqid('pick_'),
            action: 'optimization_failed',
            userId: $userId,
            metadata: [
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]
        );

        echo "✗ Optimization failed and logged\n\n";
    }
}

// =============================================================================
// Example 8: Real-Time Picker Performance Tracking
// =============================================================================

/**
 * Track picker performance metrics in real-time
 */
function trackPickerPerformance(
    PickingOptimizerInterface $pickingOptimizer,
    string $warehouseId,
    string $pickerId,
    array $completedPicks
): array {
    echo "Example 8: Real-time picker performance tracking\n";
    echo "=================================================\n\n";

    // Calculate actual pick times
    $pickMetrics = [];
    $totalActualTime = 0;
    $totalActualDistance = 0;

    foreach ($completedPicks as $pick) {
        $actualTime = $pick['completed_at'] - $pick['started_at'];
        $totalActualTime += $actualTime;

        if (isset($pick['distance_meters'])) {
            $totalActualDistance += $pick['distance_meters'];
        }
    }

    // Re-optimize to compare with actual performance
    $pickItems = array_map(function ($pick) {
        return [
            'bin_id' => $pick['bin_id'],
            'product_id' => $pick['product_id'],
            'quantity' => $pick['quantity'],
        ];
    }, $completedPicks);

    $optimizedResult = $pickingOptimizer->optimizePickRoute($warehouseId, $pickItems);

    $pickMetrics = [
        'picker_id' => $pickerId,
        'pick_count' => count($completedPicks),
        'actual_time_seconds' => $totalActualTime,
        'actual_distance_meters' => $totalActualDistance,
        'optimal_distance_meters' => $optimizedResult->getTotalDistance(),
        'efficiency_percent' => $totalActualDistance > 0 
            ? ($optimizedResult->getTotalDistance() / $totalActualDistance) * 100 
            : 100,
        'avg_time_per_pick' => $totalActualTime / count($completedPicks),
    ];

    echo "Picker Performance Metrics:\n";
    echo "  Picker ID: {$pickMetrics['picker_id']}\n";
    echo "  Picks Completed: {$pickMetrics['pick_count']}\n";
    echo "  Total Time: {$pickMetrics['actual_time_seconds']} seconds\n";
    echo "  Actual Distance: " . round($pickMetrics['actual_distance_meters'], 2) . " meters\n";
    echo "  Optimal Distance: " . round($pickMetrics['optimal_distance_meters'], 2) . " meters\n";
    echo "  Efficiency: " . round($pickMetrics['efficiency_percent'], 2) . "%\n";
    echo "  Avg Time/Pick: " . round($pickMetrics['avg_time_per_pick'], 2) . " seconds\n\n";

    return $pickMetrics;
}

// =============================================================================
// Main Advanced Examples Runner
// =============================================================================

/**
 * Run all advanced examples with realistic data
 */
function runAdvancedExamples(
    WarehouseManagerInterface $warehouseManager,
    PickingOptimizerInterface $pickingOptimizer,
    LoggerInterface $logger
): void {
    echo "========================================\n";
    echo "Advanced Warehouse Management Examples\n";
    echo "========================================\n\n";

    $logger->info('Starting Warehouse advanced usage examples');

    try {
        // Create test warehouse
        $warehouseId = $warehouseManager->createWarehouse(
            code: 'WH-ADVANCED',
            name: 'Advanced Operations Warehouse',
            metadata: ['purpose' => 'advanced_examples']
        );

        // Example 1: Create large warehouse layout
        createWarehouseLayout($warehouseId);

        // Example 2: Wave picking
        $sampleOrders = generateSampleOrders($warehouseId, 30);
        optimizeWavePicking($pickingOptimizer, $warehouseId, $sampleOrders);

        // Example 3: Zone-based optimization
        $samplePicks = generateSamplePicks($warehouseId, 50);
        optimizeByZone($pickingOptimizer, $warehouseId, $samplePicks);

        // Example 5: Performance monitoring
        monitorPickingPerformance($pickingOptimizer, $warehouseId, $samplePicks, $logger);

        // Example 6: Large pick list
        $largePicks = generateSamplePicks($warehouseId, 200);
        optimizeLargePickList($pickingOptimizer, $warehouseId, $largePicks);

        echo "✓ All advanced examples completed successfully\n";
        
        $logger->info('All Warehouse advanced usage examples completed successfully');
    } catch (\Exception $e) {
        echo "✗ Error: {$e->getMessage()}\n";
        $logger->error('Warehouse advanced usage example failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

// =============================================================================
// Helper Functions
// =============================================================================

function generateSampleOrders(string $warehouseId, int $count): array
{
    $orders = [];
    
    for ($i = 1; $i <= $count; $i++) {
        $orders[] = [
            'order_id' => "ORD-{$i}",
            'items' => generateSamplePicks($warehouseId, rand(3, 10)),
        ];
    }
    
    return $orders;
}

function generateSamplePicks(string $warehouseId, int $count): array
{
    $bins = BinLocation::where('warehouse_id', $warehouseId)
        ->inRandomOrder()
        ->limit($count)
        ->get();

    $picks = [];
    
    foreach ($bins as $bin) {
        $picks[] = [
            'bin_id' => $bin->id,
            'product_id' => 'PROD-' . rand(1000, 9999),
            'quantity' => (float) rand(1, 20),
        ];
    }
    
    return $picks;
}
