# Warehouse Package - Test Suite Summary

## Test Suite Overview

**Created:** 2025-11-28  
**PHPUnit Version:** 11.5+  
**PHP Version:** 8.3+  
**Total Test Files:** 0 (planned: 8)  
**Total Test Methods:** 0 (planned: 35+)  
**Status:** ⏳ Test Suite Pending

---

## Current State

The Warehouse package implementation is complete with 563 lines of production code across 11 PHP files. The test suite has not yet been implemented and is planned for a future development phase (estimated 10% complete).

### Package Components to Test

| Component | Type | Location | LOC | Planned Tests |
|-----------|------|----------|-----|---------------|
| WarehouseManager | Service | `src/Services/WarehouseManager.php` | 196 | 12+ tests |
| PickingOptimizer | Service | `src/Services/PickingOptimizer.php` | 150 | 10+ tests |
| WarehouseInterface | Contract | `src/Contracts/WarehouseInterface.php` | ~35 | 2+ tests |
| BinLocationInterface | Contract | `src/Contracts/BinLocationInterface.php` | ~40 | 3+ tests |
| Exceptions | Exceptions | `src/Exceptions/*.php` | ~30 | 3+ tests |
| Integration Flow | Feature | Multiple components | - | 5+ tests |

---

## Planned Test Organization

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── WarehouseManagerTest.php (12 tests)
│   │   └── PickingOptimizerTest.php (10 tests)
│   ├── Contracts/
│   │   ├── WarehouseInterfaceTest.php (2 tests)
│   │   └── BinLocationInterfaceTest.php (3 tests)
│   └── Exceptions/
│       └── ExceptionHierarchyTest.php (3 tests)
└── Feature/
    └── PickingOptimizationFlowTest.php (5 tests)
```

---

## Planned Test Coverage by Component

### 1. WarehouseManager Service (12 tests)

**File:** `tests/Unit/Services/WarehouseManagerTest.php`

#### Warehouse Creation & Retrieval (4 tests)
```php
/** @test */
public function it_creates_warehouse_with_valid_data(): void
{
    // Given: Mock repository returns saved warehouse
    $repository = $this->createMock(WarehouseRepositoryInterface::class);
    $warehouse = new ConcreteWarehouse(
        id: 'wh_001',
        code: 'WH-MAIN',
        name: 'Main Warehouse',
        metadata: ['address' => '123 Main St']
    );
    $repository->expects($this->once())
        ->method('save')
        ->with($this->callback(function ($w) {
            return $w->getCode() === 'WH-MAIN';
        }))
        ->willReturn($warehouse);

    // When: Create warehouse via manager
    $manager = new WarehouseManager($repository, $binLocationRepository);
    $result = $manager->createWarehouse('WH-MAIN', 'Main Warehouse', ['address' => '123 Main St']);

    // Then: Returns created warehouse
    $this->assertInstanceOf(WarehouseInterface::class, $result);
    $this->assertEquals('WH-MAIN', $result->getCode());
}

/** @test */
public function it_retrieves_warehouse_by_id(): void
{
    // Given: Repository returns warehouse for ID
    $repository = $this->createMock(WarehouseRepositoryInterface::class);
    $warehouse = new ConcreteWarehouse(id: 'wh_001', code: 'WH-MAIN', name: 'Main Warehouse');
    $repository->expects($this->once())
        ->method('findById')
        ->with('wh_001')
        ->willReturn($warehouse);

    // When: Get warehouse by ID
    $manager = new WarehouseManager($repository, $binLocationRepository);
    $result = $manager->getWarehouse('wh_001');

    // Then: Returns warehouse
    $this->assertEquals('wh_001', $result->getId());
}

/** @test */
public function it_throws_exception_when_warehouse_not_found(): void
{
    // Given: Repository returns null
    $repository = $this->createMock(WarehouseRepositoryInterface::class);
    $repository->expects($this->once())
        ->method('findById')
        ->with('wh_nonexistent')
        ->willReturn(null);

    // When/Then: Throws WarehouseNotFoundException
    $manager = new WarehouseManager($repository, $binLocationRepository);
    $this->expectException(WarehouseNotFoundException::class);
    $manager->getWarehouse('wh_nonexistent');
}

/** @test */
public function it_lists_all_warehouses_for_tenant(): void
{
    // Given: Repository returns list of warehouses
    $repository = $this->createMock(WarehouseRepositoryInterface::class);
    $warehouses = [
        new ConcreteWarehouse(id: 'wh_001', code: 'WH-MAIN', name: 'Main'),
        new ConcreteWarehouse(id: 'wh_002', code: 'WH-BRANCH', name: 'Branch'),
    ];
    $repository->expects($this->once())
        ->method('findAll')
        ->willReturn($warehouses);

    // When: List all warehouses
    $manager = new WarehouseManager($repository, $binLocationRepository);
    $result = $manager->getAllWarehouses();

    // Then: Returns array of warehouses
    $this->assertCount(2, $result);
}
```

#### Bin Location Management (5 tests)
```php
/** @test */
public function it_creates_bin_location_with_gps_coordinates(): void
{
    // Given: Mock repository and GPS coordinates
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $coordinates = new Coordinates(latitude: 3.1390, longitude: 101.6869);
    $binLocation = new ConcreteBinLocation(
        id: 'bin_001',
        code: 'A1-05-03',
        warehouseId: 'wh_001',
        coordinates: $coordinates
    );
    $binRepository->expects($this->once())
        ->method('save')
        ->willReturn($binLocation);

    // When: Create bin location
    $manager = new WarehouseManager($warehouseRepository, $binRepository);
    $result = $manager->createBinLocation('wh_001', 'A1-05-03', $coordinates);

    // Then: Returns bin location with coordinates
    $this->assertEquals(3.1390, $result->getCoordinates()->latitude);
}

/** @test */
public function it_creates_bin_location_without_gps_coordinates(): void
{
    // Given: Mock repository, no coordinates
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binLocation = new ConcreteBinLocation(
        id: 'bin_001',
        code: 'A1-05-03',
        warehouseId: 'wh_001',
        coordinates: null
    );
    $binRepository->expects($this->once())
        ->method('save')
        ->willReturn($binLocation);

    // When: Create bin location without coordinates
    $manager = new WarehouseManager($warehouseRepository, $binRepository);
    $result = $manager->createBinLocation('wh_001', 'A1-05-03', null);

    // Then: Returns bin location with null coordinates
    $this->assertNull($result->getCoordinates());
}

/** @test */
public function it_retrieves_bin_location_by_id(): void
{
    // Given: Repository returns bin location
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binLocation = new ConcreteBinLocation(id: 'bin_001', code: 'A1-05-03', warehouseId: 'wh_001');
    $binRepository->expects($this->once())
        ->method('findById')
        ->with('bin_001')
        ->willReturn($binLocation);

    // When: Get bin location
    $manager = new WarehouseManager($warehouseRepository, $binRepository);
    $result = $manager->getBinLocation('bin_001');

    // Then: Returns bin location
    $this->assertEquals('A1-05-03', $result->getCode());
}

/** @test */
public function it_throws_exception_when_bin_location_not_found(): void
{
    // Given: Repository returns null
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binRepository->expects($this->once())
        ->method('findById')
        ->with('bin_nonexistent')
        ->willReturn(null);

    // When/Then: Throws BinLocationNotFoundException
    $manager = new WarehouseManager($warehouseRepository, $binRepository);
    $this->expectException(BinLocationNotFoundException::class);
    $manager->getBinLocation('bin_nonexistent');
}

/** @test */
public function it_lists_bin_locations_for_warehouse(): void
{
    // Given: Repository returns bin locations for warehouse
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binLocations = [
        new ConcreteBinLocation(id: 'bin_001', code: 'A1-01', warehouseId: 'wh_001'),
        new ConcreteBinLocation(id: 'bin_002', code: 'A1-02', warehouseId: 'wh_001'),
    ];
    $binRepository->expects($this->once())
        ->method('findByWarehouseId')
        ->with('wh_001')
        ->willReturn($binLocations);

    // When: Get bin locations for warehouse
    $manager = new WarehouseManager($warehouseRepository, $binRepository);
    $result = $manager->getBinLocationsForWarehouse('wh_001');

    // Then: Returns array of bin locations
    $this->assertCount(2, $result);
}
```

#### Warehouse Updates & Deletion (3 tests)
```php
/** @test */
public function it_updates_warehouse_metadata(): void
{
    // Given: Existing warehouse
    $repository = $this->createMock(WarehouseRepositoryInterface::class);
    $warehouse = new ConcreteWarehouse(id: 'wh_001', code: 'WH-MAIN', name: 'Main Warehouse');
    $repository->expects($this->once())
        ->method('findById')
        ->with('wh_001')
        ->willReturn($warehouse);
    $repository->expects($this->once())
        ->method('save')
        ->with($this->callback(function ($w) {
            return $w->getMetadata()['capacity'] === 1000;
        }))
        ->willReturn($warehouse);

    // When: Update warehouse
    $manager = new WarehouseManager($repository, $binLocationRepository);
    $result = $manager->updateWarehouse('wh_001', ['capacity' => 1000]);

    // Then: Returns updated warehouse
    $this->assertEquals(1000, $result->getMetadata()['capacity']);
}

/** @test */
public function it_deletes_warehouse(): void
{
    // Given: Warehouse exists
    $repository = $this->createMock(WarehouseRepositoryInterface::class);
    $repository->expects($this->once())
        ->method('delete')
        ->with('wh_001');

    // When: Delete warehouse
    $manager = new WarehouseManager($repository, $binLocationRepository);
    $manager->deleteWarehouse('wh_001');

    // Then: Repository delete method called
    $this->assertTrue(true); // Assertion verified via mock expectations
}

/** @test */
public function it_deletes_bin_location(): void
{
    // Given: Bin location exists
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binRepository->expects($this->once())
        ->method('delete')
        ->with('bin_001');

    // When: Delete bin location
    $manager = new WarehouseManager($warehouseRepository, $binRepository);
    $manager->deleteBinLocation('bin_001');

    // Then: Repository delete method called
    $this->assertTrue(true);
}
```

---

### 2. PickingOptimizer Service (10 tests)

**File:** `tests/Unit/Services/PickingOptimizerTest.php`

#### TSP Route Optimization (6 tests)
```php
/** @test */
public function it_optimizes_pick_route_with_gps_coordinates(): void
{
    // Given: Mock TspOptimizer and bin locations with coordinates
    $tspOptimizer = $this->createMock(TspOptimizerInterface::class);
    $tspOptimizer->expects($this->once())
        ->method('optimize')
        ->willReturn([
            'optimized_sequence' => [0, 2, 1], // Depot, bin_c5, bin_a1
            'total_distance' => 150.5,
            'original_distance' => 200.0,
        ]);

    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binRepository->method('findById')->willReturnMap([
        ['bin_a1', new ConcreteBinLocation(id: 'bin_a1', code: 'A1', warehouseId: 'wh_001', 
            coordinates: new Coordinates(3.1390, 101.6869))],
        ['bin_c5', new ConcreteBinLocation(id: 'bin_c5', code: 'C5', warehouseId: 'wh_001',
            coordinates: new Coordinates(3.1420, 101.6900))],
    ]);

    // When: Optimize pick route
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepository);
    $result = $optimizer->optimizePickRoute('wh_001', [
        ['bin_id' => 'bin_a1', 'product_id' => 'prod_1', 'quantity' => 10],
        ['bin_id' => 'bin_c5', 'product_id' => 'prod_2', 'quantity' => 5],
    ]);

    // Then: Returns optimized route
    $this->assertEquals(150.5, $result->getTotalDistance());
    $this->assertEquals(24.75, $result->getDistanceImprovement()); // (200-150.5)/200 = 24.75%
}

/** @test */
public function it_returns_sequential_route_when_no_gps_coordinates(): void
{
    // Given: Bin locations without GPS coordinates
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binRepository->method('findById')->willReturnMap([
        ['bin_a1', new ConcreteBinLocation(id: 'bin_a1', code: 'A1', warehouseId: 'wh_001', coordinates: null)],
        ['bin_c5', new ConcreteBinLocation(id: 'bin_c5', code: 'C5', warehouseId: 'wh_001', coordinates: null)],
    ]);

    // When: Optimize pick route without coordinates
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepository);
    $result = $optimizer->optimizePickRoute('wh_001', [
        ['bin_id' => 'bin_a1', 'product_id' => 'prod_1', 'quantity' => 10],
        ['bin_id' => 'bin_c5', 'product_id' => 'prod_2', 'quantity' => 5],
    ]);

    // Then: Returns sequential route (lexicographic ordering by bin code)
    $this->assertEquals(['bin_a1', 'bin_c5'], $result->getOptimizedSequence());
    $this->assertEquals(0, $result->getDistanceImprovement()); // No optimization without coordinates
}

/** @test */
public function it_handles_single_bin_pick_list(): void
{
    // Given: Pick list with only one bin
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binRepository->method('findById')
        ->with('bin_a1')
        ->willReturn(new ConcreteBinLocation(id: 'bin_a1', code: 'A1', warehouseId: 'wh_001'));

    // When: Optimize single bin pick route
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepository);
    $result = $optimizer->optimizePickRoute('wh_001', [
        ['bin_id' => 'bin_a1', 'product_id' => 'prod_1', 'quantity' => 10],
    ]);

    // Then: Returns single bin route
    $this->assertEquals(['bin_a1'], $result->getOptimizedSequence());
    $this->assertEquals(0, $result->getDistanceImprovement()); // No optimization needed
}

/** @test */
public function it_throws_exception_when_bin_not_found_during_optimization(): void
{
    // Given: Repository returns null for bin_nonexistent
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binRepository->method('findById')
        ->with('bin_nonexistent')
        ->willReturn(null);

    // When/Then: Throws BinLocationNotFoundException
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepository);
    $this->expectException(BinLocationNotFoundException::class);
    $optimizer->optimizePickRoute('wh_001', [
        ['bin_id' => 'bin_nonexistent', 'product_id' => 'prod_1', 'quantity' => 10],
    ]);
}

/** @test */
public function it_throws_exception_when_tsp_optimization_fails(): void
{
    // Given: TspOptimizer throws exception
    $tspOptimizer = $this->createMock(TspOptimizerInterface::class);
    $tspOptimizer->expects($this->once())
        ->method('optimize')
        ->willThrowException(new \RuntimeException('TSP solver timeout'));

    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $binRepository->method('findById')->willReturn(
        new ConcreteBinLocation(id: 'bin_a1', code: 'A1', warehouseId: 'wh_001',
            coordinates: new Coordinates(3.1390, 101.6869))
    );

    // When/Then: Throws OptimizationException
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepository);
    $this->expectException(OptimizationException::class);
    $optimizer->optimizePickRoute('wh_001', [
        ['bin_id' => 'bin_a1', 'product_id' => 'prod_1', 'quantity' => 10],
    ]);
}

/** @test */
public function it_handles_large_pick_lists_efficiently(): void
{
    // Given: Pick list with 100 bin locations
    $binRepository = $this->createMock(BinLocationRepositoryInterface::class);
    $pickItems = [];
    for ($i = 1; $i <= 100; $i++) {
        $binId = "bin_{$i}";
        $pickItems[] = ['bin_id' => $binId, 'product_id' => "prod_{$i}", 'quantity' => 1];
        $binRepository->method('findById')->with($binId)->willReturn(
            new ConcreteBinLocation(id: $binId, code: "BIN-{$i}", warehouseId: 'wh_001',
                coordinates: new Coordinates(3.1390 + $i * 0.0001, 101.6869 + $i * 0.0001))
        );
    }

    $tspOptimizer = $this->createMock(TspOptimizerInterface::class);
    $tspOptimizer->expects($this->once())
        ->method('optimize')
        ->willReturn([
            'optimized_sequence' => array_merge([0], range(1, 100)), // Simplified
            'total_distance' => 5000,
            'original_distance' => 7000,
        ]);

    // When: Optimize large pick list
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepository);
    $startTime = microtime(true);
    $result = $optimizer->optimizePickRoute('wh_001', $pickItems);
    $executionTime = microtime(true) - $startTime;

    // Then: Completes in reasonable time (<1 second)
    $this->assertLessThan(1.0, $executionTime);
    $this->assertGreaterThan(20, $result->getDistanceImprovement()); // At least 20% improvement
}
```

#### Distance Calculation (2 tests)
```php
/** @test */
public function it_calculates_distance_between_coordinates_correctly(): void
{
    // Given: Two coordinates (Haversine formula verification)
    $coord1 = new Coordinates(3.1390, 101.6869);
    $coord2 = new Coordinates(3.1420, 101.6900);

    // When: Calculate distance (internal method - test via optimization)
    $tspOptimizer = $this->createMock(TspOptimizerInterface::class);
    $tspOptimizer->expects($this->once())
        ->method('optimize')
        ->with($this->callback(function ($distanceMatrix) {
            // Verify distance matrix has correct values
            return abs($distanceMatrix[0][1] - 0.43) < 0.1; // ~0.43 km
        }));

    // Then: Distance matrix passed to TSP is accurate
    // (Actual test happens in callback above)
}

/** @test */
public function it_handles_zero_distance_when_coordinates_are_identical(): void
{
    // Given: Two identical coordinates
    $coord = new Coordinates(3.1390, 101.6869);
    
    // When: Calculate distance between identical points
    // Then: Distance should be 0
    $this->assertEquals(0, $optimizer->calculateDistance($coord, $coord));
}
```

#### Optimization Result (2 tests)
```php
/** @test */
public function it_provides_optimization_result_with_metrics(): void
{
    // Given: Optimized route result
    $result = new PickingOptimizationResult(
        optimizedSequence: ['bin_c5', 'bin_a1'],
        totalDistance: 150.5,
        originalDistance: 200.0,
        executionTime: 0.05
    );

    // When: Access result metrics
    // Then: Returns correct values
    $this->assertEquals(['bin_c5', 'bin_a1'], $result->getOptimizedSequence());
    $this->assertEquals(150.5, $result->getTotalDistance());
    $this->assertEquals(200.0, $result->getOriginalDistance());
    $this->assertEquals(24.75, $result->getDistanceImprovement());
    $this->assertEquals(0.05, $result->getExecutionTime());
}

/** @test */
public function it_formats_distance_improvement_as_percentage(): void
{
    // Given: Optimization result
    $result = new PickingOptimizationResult(
        optimizedSequence: ['bin_a1'],
        totalDistance: 120.0,
        originalDistance: 160.0
    );

    // When: Get distance improvement
    $improvement = $result->getDistanceImprovement();

    // Then: Returns percentage (25%)
    $this->assertEquals(25.0, $improvement);
}
```

---

### 3. Contract Tests (5 tests)

**File:** `tests/Unit/Contracts/WarehouseInterfaceTest.php` (2 tests)
```php
/** @test */
public function it_implements_warehouse_interface_contract(): void
{
    // Given: Concrete warehouse implementation
    $warehouse = new ConcreteWarehouse(
        id: 'wh_001',
        code: 'WH-MAIN',
        name: 'Main Warehouse',
        metadata: ['address' => '123 Main St']
    );

    // Then: Implements WarehouseInterface
    $this->assertInstanceOf(WarehouseInterface::class, $warehouse);
}

/** @test */
public function it_exposes_required_warehouse_properties(): void
{
    // Given: Warehouse instance
    $warehouse = new ConcreteWarehouse(
        id: 'wh_001',
        code: 'WH-MAIN',
        name: 'Main Warehouse',
        metadata: ['capacity' => 1000]
    );

    // Then: Exposes all required properties
    $this->assertEquals('wh_001', $warehouse->getId());
    $this->assertEquals('WH-MAIN', $warehouse->getCode());
    $this->assertEquals('Main Warehouse', $warehouse->getName());
    $this->assertEquals(['capacity' => 1000], $warehouse->getMetadata());
}
```

**File:** `tests/Unit/Contracts/BinLocationInterfaceTest.php` (3 tests)
```php
/** @test */
public function it_implements_bin_location_interface_contract(): void
{
    // Given: Concrete bin location implementation
    $binLocation = new ConcreteBinLocation(
        id: 'bin_001',
        code: 'A1-05-03',
        warehouseId: 'wh_001',
        coordinates: new Coordinates(3.1390, 101.6869)
    );

    // Then: Implements BinLocationInterface
    $this->assertInstanceOf(BinLocationInterface::class, $binLocation);
}

/** @test */
public function it_exposes_required_bin_location_properties_with_coordinates(): void
{
    // Given: Bin location with GPS coordinates
    $coordinates = new Coordinates(3.1390, 101.6869);
    $binLocation = new ConcreteBinLocation(
        id: 'bin_001',
        code: 'A1-05-03',
        warehouseId: 'wh_001',
        coordinates: $coordinates
    );

    // Then: Exposes all required properties
    $this->assertEquals('bin_001', $binLocation->getId());
    $this->assertEquals('A1-05-03', $binLocation->getCode());
    $this->assertEquals('wh_001', $binLocation->getWarehouseId());
    $this->assertInstanceOf(Coordinates::class, $binLocation->getCoordinates());
    $this->assertEquals(3.1390, $binLocation->getCoordinates()->latitude);
}

/** @test */
public function it_allows_null_coordinates_for_bin_locations(): void
{
    // Given: Bin location without GPS coordinates
    $binLocation = new ConcreteBinLocation(
        id: 'bin_001',
        code: 'A1-05-03',
        warehouseId: 'wh_001',
        coordinates: null
    );

    // Then: Coordinates are null
    $this->assertNull($binLocation->getCoordinates());
}
```

---

### 4. Exception Tests (3 tests)

**File:** `tests/Unit/Exceptions/ExceptionHierarchyTest.php`

```php
/** @test */
public function it_throws_warehouse_not_found_exception(): void
{
    // Given: Warehouse ID that doesn't exist
    $warehouseId = 'wh_nonexistent';

    // When/Then: Throws WarehouseNotFoundException
    $exception = new WarehouseNotFoundException($warehouseId);
    $this->assertInstanceOf(\RuntimeException::class, $exception);
    $this->assertStringContainsString('wh_nonexistent', $exception->getMessage());
}

/** @test */
public function it_throws_bin_location_not_found_exception(): void
{
    // Given: Bin location ID that doesn't exist
    $binId = 'bin_nonexistent';

    // When/Then: Throws BinLocationNotFoundException
    $exception = new BinLocationNotFoundException($binId);
    $this->assertInstanceOf(\RuntimeException::class, $exception);
    $this->assertStringContainsString('bin_nonexistent', $exception->getMessage());
}

/** @test */
public function it_throws_optimization_exception_with_context(): void
{
    // Given: TSP optimization failure
    $context = 'TSP solver timeout after 30 seconds';

    // When/Then: Throws OptimizationException
    $exception = new OptimizationException($context);
    $this->assertInstanceOf(\RuntimeException::class, $exception);
    $this->assertStringContainsString('timeout', $exception->getMessage());
}
```

---

### 5. Integration Tests (5 tests)

**File:** `tests/Feature/PickingOptimizationFlowTest.php`

```php
/** @test */
public function it_completes_full_picking_optimization_flow(): void
{
    // Given: Complete warehouse setup
    $warehouseRepo = new InMemoryWarehouseRepository();
    $binRepo = new InMemoryBinLocationRepository();
    $tspOptimizer = new GoogleORToolsTspOptimizer();

    $manager = new WarehouseManager($warehouseRepo, $binRepo);
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepo);

    // Create warehouse
    $warehouse = $manager->createWarehouse('WH-MAIN', 'Main Warehouse');

    // Create bin locations with GPS coordinates
    $binA1 = $manager->createBinLocation($warehouse->getId(), 'A1', new Coordinates(3.1390, 101.6869));
    $binC5 = $manager->createBinLocation($warehouse->getId(), 'C5', new Coordinates(3.1420, 101.6900));
    $binB3 = $manager->createBinLocation($warehouse->getId(), 'B3', new Coordinates(3.1405, 101.6885));

    // When: Optimize pick route
    $result = $optimizer->optimizePickRoute($warehouse->getId(), [
        ['bin_id' => $binA1->getId(), 'product_id' => 'prod_1', 'quantity' => 10],
        ['bin_id' => $binC5->getId(), 'product_id' => 'prod_2', 'quantity' => 5],
        ['bin_id' => $binB3->getId(), 'product_id' => 'prod_3', 'quantity' => 8],
    ]);

    // Then: Returns optimized route with distance reduction
    $this->assertCount(3, $result->getOptimizedSequence());
    $this->assertGreaterThan(10, $result->getDistanceImprovement()); // At least 10% improvement
}

/** @test */
public function it_handles_warehouse_with_no_gps_coordinates(): void
{
    // Given: Warehouse with bin locations lacking GPS coordinates
    $manager = new WarehouseManager($warehouseRepo, $binRepo);
    $warehouse = $manager->createWarehouse('WH-LEGACY', 'Legacy Warehouse');
    $binA1 = $manager->createBinLocation($warehouse->getId(), 'A1', null);
    $binC5 = $manager->createBinLocation($warehouse->getId(), 'C5', null);

    // When: Optimize pick route without coordinates
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepo);
    $result = $optimizer->optimizePickRoute($warehouse->getId(), [
        ['bin_id' => $binA1->getId(), 'product_id' => 'prod_1', 'quantity' => 10],
        ['bin_id' => $binC5->getId(), 'product_id' => 'prod_2', 'quantity' => 5],
    ]);

    // Then: Returns lexicographic sequential route
    $this->assertEquals([$binA1->getId(), $binC5->getId()], $result->getOptimizedSequence());
    $this->assertEquals(0, $result->getDistanceImprovement());
}

/** @test */
public function it_validates_bin_locations_belong_to_warehouse(): void
{
    // Given: Two warehouses with different bin locations
    $warehouse1 = $manager->createWarehouse('WH-MAIN', 'Main Warehouse');
    $warehouse2 = $manager->createWarehouse('WH-BRANCH', 'Branch Warehouse');
    $binA1 = $manager->createBinLocation($warehouse1->getId(), 'A1', new Coordinates(3.1390, 101.6869));
    $binB1 = $manager->createBinLocation($warehouse2->getId(), 'B1', new Coordinates(3.2000, 101.7000));

    // When: Try to optimize pick route with bins from different warehouses
    // Then: Should throw exception or filter bins
    $this->expectException(OptimizationException::class);
    $optimizer->optimizePickRoute($warehouse1->getId(), [
        ['bin_id' => $binA1->getId(), 'product_id' => 'prod_1', 'quantity' => 10],
        ['bin_id' => $binB1->getId(), 'product_id' => 'prod_2', 'quantity' => 5], // Wrong warehouse!
    ]);
}

/** @test */
public function it_caches_bin_location_coordinates_for_performance(): void
{
    // Given: Mock repository with call tracking
    $binRepo = $this->createMock(BinLocationRepositoryInterface::class);
    $binRepo->expects($this->exactly(1)) // Called only once despite multiple optimizations
        ->method('findById')
        ->with('bin_a1')
        ->willReturn(new ConcreteBinLocation(id: 'bin_a1', code: 'A1', warehouseId: 'wh_001',
            coordinates: new Coordinates(3.1390, 101.6869)));

    // When: Optimize same pick list twice
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepo);
    $optimizer->optimizePickRoute('wh_001', [['bin_id' => 'bin_a1', 'product_id' => 'prod_1', 'quantity' => 10]]);
    $optimizer->optimizePickRoute('wh_001', [['bin_id' => 'bin_a1', 'product_id' => 'prod_1', 'quantity' => 10]]);

    // Then: Repository called only once (caching works)
    // Assertion verified via mock expectations
}

/** @test */
public function it_measures_tsp_optimization_execution_time(): void
{
    // Given: Real TSP optimizer with timing
    $optimizer = new PickingOptimizer($tspOptimizer, $binRepo);
    $warehouse = $manager->createWarehouse('WH-PERF', 'Performance Test Warehouse');
    
    // Create 50 bin locations
    $pickItems = [];
    for ($i = 1; $i <= 50; $i++) {
        $bin = $manager->createBinLocation($warehouse->getId(), "BIN-{$i}",
            new Coordinates(3.1390 + $i * 0.0001, 101.6869 + $i * 0.0001));
        $pickItems[] = ['bin_id' => $bin->getId(), 'product_id' => "prod_{$i}", 'quantity' => 1];
    }

    // When: Optimize pick route and measure time
    $result = $optimizer->optimizePickRoute($warehouse->getId(), $pickItems);

    // Then: Execution time is tracked and reasonable (<500ms for 50 locations)
    $this->assertLessThan(0.5, $result->getExecutionTime());
}
```

---

## Test Infrastructure Requirements

### Mock Implementations
The test suite will require mock implementations for:

1. **InMemoryWarehouseRepository** - In-memory warehouse storage for integration tests
2. **InMemoryBinLocationRepository** - In-memory bin location storage for integration tests
3. **ConcreteWarehouse** - Simple warehouse entity implementation
4. **ConcreteBinLocation** - Simple bin location entity implementation
5. **PickingOptimizationResult** - Value object for optimization results

### PHPUnit Configuration
```xml
<!-- phpunit.xml -->
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory>src</directory>
        </include>
    </coverage>
</phpunit>
```

---

## Test Execution Plan

### Phase 1: Basic Unit Tests (Estimated: 16 hours)
- [ ] WarehouseManager basic CRUD tests (4 hours)
- [ ] PickingOptimizer basic optimization tests (6 hours)
- [ ] Contract implementation tests (2 hours)
- [ ] Exception hierarchy tests (1 hour)
- [ ] Setup test infrastructure (mock repositories) (3 hours)

### Phase 2: Advanced Unit Tests (Estimated: 8 hours)
- [ ] Edge case handling (empty pick lists, single bin, null coordinates) (4 hours)
- [ ] Performance tests (large pick lists) (2 hours)
- [ ] Error handling tests (optimization failures) (2 hours)

### Phase 3: Integration Tests (Estimated: 12 hours)
- [ ] Full picking optimization flow (4 hours)
- [ ] Multi-warehouse scenarios (3 hours)
- [ ] Caching behavior validation (2 hours)
- [ ] Performance benchmarking (3 hours)

**Total Estimated Effort:** 36 hours @ $75/hr = **$2,700**

---

## Expected Test Coverage

### Target Coverage Metrics
- **Line Coverage:** 85%+ (target for production-ready packages)
- **Branch Coverage:** 80%+ (all major code paths tested)
- **Method Coverage:** 95%+ (all public methods tested)

### Coverage by Component
| Component | Expected Coverage | Rationale |
|-----------|-------------------|-----------|
| WarehouseManager | 90%+ | Critical business logic, all public methods tested |
| PickingOptimizer | 85%+ | Complex TSP integration, edge cases covered |
| Interfaces | 100% | Contract compliance verified |
| Exceptions | 100% | All exception types instantiated |

---

## Testing Best Practices

### Mocking Strategy
- **Mock external dependencies:** `TspOptimizerInterface`, `WarehouseRepositoryInterface`, `BinLocationRepositoryInterface`
- **Use real value objects:** `Coordinates` from `Nexus\Geo`
- **Test boundary conditions:** Empty lists, single items, null coordinates, large lists
- **Verify exception messages:** Ensure error messages contain useful debugging information

### Performance Testing
- **Measure TSP optimization time** for pick lists of varying sizes (10, 50, 100, 500 items)
- **Verify <500ms optimization time** for typical pick list size (50 items)
- **Test memory usage** for large pick lists (avoid memory leaks)

### Integration Test Strategy
- **Use in-memory repositories** for fast, isolated integration tests
- **Test full workflows** (create warehouse → create bins → optimize route)
- **Validate multi-warehouse scenarios** (ensure bin locations scoped to warehouse)

---

## Notes

### Test Suite Status
The Warehouse package currently has **no tests implemented** (0% coverage). This test suite summary documents the **planned test strategy** to achieve production-ready quality.

### Priority Tests
If implementing tests incrementally, prioritize:
1. **PickingOptimizer TSP optimization** (core feature)
2. **WarehouseManager CRUD operations** (essential functionality)
3. **Exception handling** (error scenarios)
4. **Integration flow tests** (end-to-end validation)

### Known Testing Challenges
1. **TSP Optimization Non-Determinism** - TSP heuristic may return different routes; test for distance improvement % rather than exact sequence
2. **GPS Coordinate Precision** - Haversine formula accuracy depends on coordinate precision; use approximate assertions
3. **Performance Benchmarking** - TSP performance varies by hardware; set reasonable time limits

---

**Prepared By:** Nexus QA Team  
**Last Updated:** 2025-11-28  
**Review Status:** Pending Implementation
