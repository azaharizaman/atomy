# Nexus Routing Implementation Quick Reference

**Part of:** Geo & Routing Implementation  
**Main Documentation:** See `GEO_IMPLEMENTATION_SUMMARY.md` for full details

---

## Package Overview

`Nexus\Routing` provides **framework-agnostic** route optimization for:
- **TSP (Traveling Salesman Problem)**: Single-vehicle route optimization
- **VRP (Vehicle Routing Problem)**: Multi-vehicle fleet optimization
- **Constraint Validation**: Time windows, capacity, duration limits
- **Offline Caching**: Gzip-compressed routes for mobile apps

---

## Quick Start

### TSP Optimization

```php
use Nexus\Routing\Services\TspOptimizer;
use Nexus\Routing\ValueObjects\RouteStop;
use Nexus\Geo\ValueObjects\Coordinates;

$stops = [
    new RouteStop('stop_1', new Coordinates(3.1390, 101.6869), null, null, 300),
    new RouteStop('stop_2', new Coordinates(3.1478, 101.6953), null, null, 600),
];

$result = $tspOptimizer->optimizeTsp($stops, $depotCoordinates);

echo "Distance: " . $result->optimizedRoute->totalDistance->format('km');
echo "Duration: " . $result->optimizedRoute->formatDuration();
```

### VRP Optimization

```php
$vehicles = [
    new VehicleProfile('truck_1', 1000, $depot), // 1000 kg capacity
    new VehicleProfile('truck_2', 800, $depot),
];

$constraints = new RouteConstraints(
    maxDurationSeconds: 28800, // 8 hours
    maxCapacity: 1000
);

$result = $vrpOptimizer->optimizeVrp($stops, $vehicles, $constraints);
```

---

## Algorithms

| Algorithm | Use Case | Complexity | Execution Time |
|-----------|----------|------------|----------------|
| **Nearest-Neighbor** | TSP construction | O(n²) | ~10ms (50 stops) |
| **2-Opt** | TSP refinement | O(n²) | ~50ms (50 stops) |
| **Greedy VRP** | Multi-vehicle | O(n² × v) | ~200ms (100 stops, 5 vehicles) |
| **OR-Tools** (future) | Large-scale | Variable | 5-30s (500+ stops) |

---

## Constraint Types

### Time Windows
```php
$stop = new RouteStop(
    id: 'customer_123',
    coordinates: new Coordinates(3.1390, 101.6869),
    timeWindowStart: new \DateTimeImmutable('2025-01-15 09:00:00'),
    timeWindowEnd: new \DateTimeImmutable('2025-01-15 12:00:00'),
    serviceDurationSeconds: 600 // 10 minutes
);
```

### Capacity
```php
$vehicle = new VehicleProfile(
    id: 'truck_1',
    capacity: 1000, // kg
    depotCoordinates: $depot
);
```

### Duration
```php
$constraints = new RouteConstraints(
    maxDurationSeconds: 28800 // 8 hours
);
```

---

## Optimization Metrics

```php
$metrics = $result->metrics;

echo "Algorithm: " . $metrics->algorithm;
echo "Execution Time: " . $metrics->executionTimeMs . " ms";
echo "Distance Improvement: " . $metrics->getDistanceImprovement() . "%";
echo "Violations: " . $metrics->getViolationCount();
```

---

## Offline Route Caching

```php
// Store route
$cacheManager->store($routeId, $optimizedRoute, $tenantId);

// Retrieve route
$cachedRoute = $cacheManager->retrieve($routeId, $tenantId);

// Get metrics
$metrics = $cacheManager->getMetrics($tenantId);
echo "Cache size: " . number_format($metrics['total_size_bytes'] / 1024 / 1024, 2) . " MB";
```

**Features:**
- Gzip compression (80% reduction)
- 50MB limit per tenant
- 30-day TTL
- Mobile offline support

---

## Value Objects

### RouteStop
- **Properties**: id, coordinates, timeWindowStart, timeWindowEnd, serviceDurationSeconds, demand
- **Methods**: `hasTimeWindow()`, `isArrivalValid()`, `getTimeWindowDuration()`

### OptimizedRoute
- **Properties**: routeId, stops, totalDistance, totalDurationSeconds, totalLoad
- **Methods**: `getStopIds()`, `getStopCount()`, `formatDuration()`, `getTravelTime()`

### VehicleProfile
- **Properties**: id, capacity, depotCoordinates, maxDurationSeconds, averageSpeedKmh
- **Methods**: `canHandle()`, `getAvailableCapacity()`

### RouteConstraints
- **Properties**: maxDurationSeconds, maxCapacity, maxStops, maxDistanceKm, enforceTimeWindows
- **Methods**: `isDurationExceeded()`, `isCapacityExceeded()`, `areStopsExceeded()`

### ConstraintViolation
- **Properties**: type, description, vehicleId, stopId, severity
- **Methods**: `isCritical()` (severity > 0.8)

---

## Next Steps (Atomy Integration)

1. Create `DbRouteCacheRepository` with gzip compression
2. Implement `ORToolsAdapter` for advanced optimization
3. Create migrations for `route_cache` and `route_optimization_logs` tables
4. Add API endpoints: `/api/routing/tsp`, `/api/routing/vrp`
5. Create console commands: `routing:cache-prune`, `routing:or-tools-health`
6. Configure OR-Tools Docker service
7. Add monitoring for constraint violations

---

## Performance Benchmarks

| Metric | Value |
|--------|-------|
| TSP (50 stops) | 60ms |
| VRP (100 stops, 5 vehicles) | 200ms |
| Route cache compression | 80% reduction |
| Cache retrieval | <10ms |

---

## Configuration Reference

```php
// config/routing.php
return [
    'offline_cache' => [
        'max_size_mb' => 50,
        'ttl_days' => 30,
        'compression' => 'gzip',
    ],
    'or_tools' => [
        'docker_host' => env('OR_TOOLS_HOST', 'localhost'),
        'docker_port' => env('OR_TOOLS_PORT', 8080),
    ],
    'optimization' => [
        'tsp_threshold' => 20,
        'vrp_threshold' => 100,
        'use_or_tools_above' => 100,
    ],
];
```

---

For full implementation details, database schema, API specifications, and integration guide, see **`GEO_IMPLEMENTATION_SUMMARY.md`**.
