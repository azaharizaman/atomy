# Nexus\Routing

Route optimization and Vehicle Routing Problem (VRP) solver for the Nexus ERP system.

## Overview

`Nexus\Routing` provides **framework-agnostic** algorithms for solving Traveling Salesman Problem (TSP) and Vehicle Routing Problem (VRP) with support for:

- **TSP Optimization**: Nearest-Neighbor with 2-Opt refinement
- **Multi-Vehicle VRP**: Google OR-Tools integration for complex constraints
- **Offline Routing Cache**: Gzip-compressed route storage (50MB limit)
- **Constraint Validation**: Time windows, vehicle capacity, service duration
- **Route Metrics**: Total distance, duration, constraint violations

This package depends on `nexus/geo` for distance calculations and coordinates.

## Installation

```bash
composer require nexus/routing:*@dev
```

## Features

### 1. TSP Optimization (Single Vehicle)

Optimizes route for a single vehicle visiting multiple stops:

```php
use Nexus\Routing\Services\TspOptimizer;
use Nexus\Routing\ValueObjects\RouteStop;
use Nexus\Geo\ValueObjects\Coordinates;

$stops = [
    new RouteStop('stop_1', new Coordinates(3.1390, 101.6869), null, null, 300), // 5 min service
    new RouteStop('stop_2', new Coordinates(3.1478, 101.6953), null, null, 600),
    new RouteStop('stop_3', new Coordinates(3.1570, 101.7123), null, null, 450),
];

$result = $tspOptimizer->optimize($stops, $depotCoordinates);

echo "Total Distance: " . $result->optimizedRoute->totalDistance->format('km');
echo "Total Duration: " . gmdate('H:i:s', $result->optimizedRoute->totalDurationSeconds);
echo "Optimized Sequence: " . implode(' -> ', $result->optimizedRoute->getStopIds());
```

**Output:**
```
Total Distance: 12.45 km
Total Duration: 00:42:30
Optimized Sequence: depot -> stop_2 -> stop_3 -> stop_1 -> depot
```

### 2. VRP Optimization (Multi-Vehicle)

Assigns stops to multiple vehicles with capacity constraints:

```php
use Nexus\Routing\Services\VrpOptimizer;
use Nexus\Routing\ValueObjects\VehicleProfile;
use Nexus\Routing\ValueObjects\RouteConstraints;

$vehicles = [
    new VehicleProfile('truck_1', 1000, $depotCoordinates), // 1000 kg capacity
    new VehicleProfile('truck_2', 800, $depotCoordinates),
];

$constraints = new RouteConstraints(
    maxDurationSeconds: 28800, // 8 hours
    maxCapacity: 1000
);

$result = $vrpOptimizer->optimize($stops, $vehicles, $constraints);

foreach ($result->routes as $vehicleId => $route) {
    echo "Vehicle {$vehicleId}: " . $route->totalDistance->format('km');
}
```

### 3. Time Window Constraints

Enforce delivery time windows:

```php
$stop = new RouteStop(
    id: 'customer_123',
    coordinates: new Coordinates(3.1390, 101.6869),
    timeWindowStart: new \DateTimeImmutable('2025-01-15 09:00:00'),
    timeWindowEnd: new \DateTimeImmutable('2025-01-15 12:00:00'),
    serviceDurationSeconds: 600, // 10 minutes
    demand: 50 // 50 kg
);

$violations = $constraintValidator->validate($route, $constraints);

if (!empty($violations)) {
    foreach ($violations as $violation) {
        echo "{$violation->type}: {$violation->description}";
    }
}
```

### 4. Offline Route Cache

Store optimized routes for offline mobile app access:

```php
use Nexus\Routing\Services\OfflineRouteCacheManager;

// Store route
$cacheManager->store($routeId, $optimizedRoute, $tenantId);

// Retrieve for offline use
$cachedRoute = $cacheManager->retrieve($routeId, $tenantId);

// Get cache metrics
$metrics = $cacheManager->getMetrics($tenantId);
echo "Total cached routes: {$metrics->totalRoutes}";
echo "Cache size: " . number_format($metrics->totalSizeBytes / 1024 / 1024, 2) . " MB";
```

**Cache Characteristics:**
- **Compression**: Gzip compression (typically 80% reduction)
- **Size Limit**: 50MB per tenant
- **TTL**: 30 days (configurable)
- **Versioning**: Cache key includes route parameters hash

### 5. OR-Tools Integration

For complex VRP scenarios, integrate with Google OR-Tools Docker service:

```php
// In Atomy adapter (apps/Atomy/app/Services/ORToolsAdapter.php)
$result = $orToolsAdapter->solve($stops, $vehicles, $constraints);

// OR-Tools provides:
// - Advanced constraints (pickup/delivery pairs, vehicle breaks)
// - Metaheuristics (Simulated Annealing, Tabu Search)
// - Large-scale optimization (1000+ stops)
```

## Architecture

### Package Structure

```
packages/Routing/
├── src/
│   ├── Contracts/
│   │   ├── RouteOptimizerInterface.php
│   │   ├── RouteCacheInterface.php
│   │   └── ConstraintValidatorInterface.php
│   ├── Services/
│   │   ├── TspOptimizer.php           # Nearest-Neighbor + 2-Opt
│   │   ├── VrpOptimizer.php           # Multi-vehicle assignment
│   │   └── OfflineRouteCacheManager.php
│   ├── ValueObjects/
│   │   ├── RouteStop.php
│   │   ├── OptimizedRoute.php
│   │   ├── VehicleProfile.php
│   │   ├── RouteConstraints.php
│   │   ├── RouteOptimizationResult.php
│   │   ├── ConstraintViolation.php
│   │   └── OptimizationMetrics.php
│   ├── Exceptions/
│   │   ├── RouteOptimizationException.php
│   │   ├── InvalidConstraintException.php
│   │   └── NoFeasibleSolutionException.php
│   └── ServiceProvider.php
├── composer.json
├── LICENSE
└── README.md
```

### Algorithm Performance

| Algorithm | Stops | Vehicles | Time Complexity | Execution Time |
|-----------|-------|----------|-----------------|----------------|
| **Nearest-Neighbor** | 50 | 1 | O(n²) | ~10ms |
| **2-Opt Refinement** | 50 | 1 | O(n²) | ~50ms |
| **VRP (Greedy)** | 100 | 5 | O(n² × v) | ~200ms |
| **OR-Tools (Meta)** | 500 | 10 | Variable | 5-30s |

### Cost Optimization Strategies

1. **Cache Reusable Routes**: Store frequently-requested routes (e.g., daily delivery routes)
2. **Batch Optimization**: Combine multiple route requests for OR-Tools
3. **Tiered Approach**: Use TSP for <20 stops, VRP for 20-100, OR-Tools for >100
4. **Offline Priority**: Deliver cache to mobile apps to reduce real-time API calls

## Integration with Nexus\Geo

This package tightly integrates with `Nexus\Geo`:

```php
use Nexus\Geo\Services\DistanceCalculator;
use Nexus\Geo\Services\TravelTimeEstimator;
use Nexus\Routing\Services\TspOptimizer;

$distanceCalculator = new DistanceCalculator();
$travelTimeEstimator = new TravelTimeEstimator($distanceCalculator);

$tspOptimizer = new TspOptimizer($distanceCalculator, $travelTimeEstimator, $logger);
```

## Constraint Violation Logging

All constraint violations are logged for analytics:

```php
// Violations are tracked in OptimizationMetrics
$metrics = $result->metrics;

foreach ($metrics->violations as $violation) {
    // Log to Nexus\Analytics
    $analyticsLogger->logConstraintViolation($violation);
}

// Query violations trend
$trendData = $analyticsService->getViolationTrend($tenantId, $startDate, $endDate);
```

## Configuration

Configuration is managed in `config/routing.php` (in Atomy):

```php
return [
    'offline_cache' => [
        'enabled' => true,
        'max_size_mb' => 50,
        'ttl_days' => 30,
        'compression' => 'gzip',
    ],
    'or_tools' => [
        'docker_host' => env('OR_TOOLS_HOST', 'localhost'),
        'docker_port' => env('OR_TOOLS_PORT', 8080),
        'timeout_seconds' => 60,
    ],
    'optimization' => [
        'tsp_threshold' => 20,      // Use TSP for ≤20 stops
        'vrp_threshold' => 100,     // Use VRP for ≤100 stops
        'use_or_tools_above' => 100, // Use OR-Tools for >100 stops
    ],
];
```

## Testing

```bash
vendor/bin/phpunit packages/Routing/tests
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Dependencies

- `nexus/geo`: Geospatial calculations
- `psr/log`: Logging interface
- Google OR-Tools (optional, via Docker)

## Contributing

This package follows the Nexus monorepo **Logic in Packages, Implementation in Applications** architecture. All business logic stays framework-agnostic.
