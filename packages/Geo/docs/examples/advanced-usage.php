<?php

/**
 * Advanced Usage Example: Nexus Geo Package
 * 
 * This example demonstrates advanced scenarios:
 * 1. Batch geocoding with rate limiting
 * 2. Polygon simplification for complex geofences
 * 3. Bearing calculation and compass direction
 * 4. Travel time estimation
 * 5. Multi-warehouse nearest location finder
 * 6. Dynamic geofence creation and validation
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Geo\Services\GeocodingManager;
use Nexus\Geo\Services\DistanceCalculator;
use Nexus\Geo\Services\BearingCalculator;
use Nexus\Geo\Services\GeofenceManager;
use Nexus\Geo\Services\PolygonSimplifier;
use Nexus\Geo\Services\TravelTimeEstimator;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\Polygon;
use Nexus\Geo\ValueObjects\Distance;
use Nexus\Geo\Enums\SpeedUnit;
use Nexus\Geo\Enums\CompassDirection;
use Nexus\Geo\Exceptions\GeocodingFailedException;
use Nexus\Geo\Exceptions\GeofenceValidationException;

// ============================================================
// Example 1: Batch Geocoding with Rate Limiting
// ============================================================

echo "Example 1: Batch Geocoding with Rate Limiting\n";
echo "==============================================\n\n";

function batchGeocodeAddresses(array $addresses, GeocodingManager $geocoding): array
{
    $results = [];
    $successCount = 0;
    $failureCount = 0;
    
    echo "Processing " . count($addresses) . " addresses...\n";
    
    foreach ($addresses as $index => $address) {
        try {
            echo "[" . ($index + 1) . "/" . count($addresses) . "] Geocoding: {$address}... ";
            
            $result = $geocoding->geocode($address);
            
            $results[] = [
                'address' => $address,
                'formatted_address' => $result->formattedAddress,
                'coordinates' => $result->coordinates->toArray(),
                'provider' => $result->provider,
                'status' => 'success'
            ];
            
            echo "✓ ({$result->provider})\n";
            $successCount++;
            
            // Rate limiting: 100ms between requests to respect provider limits
            usleep(100000);
            
        } catch (GeocodingFailedException $e) {
            $results[] = [
                'address' => $address,
                'error' => $e->getMessage(),
                'status' => 'failed'
            ];
            
            echo "✗ Failed\n";
            $failureCount++;
        }
    }
    
    echo "\nSummary:\n";
    echo "Success: {$successCount}\n";
    echo "Failed: {$failureCount}\n";
    echo "Success Rate: " . round(($successCount / count($addresses)) * 100, 2) . "%\n\n";
    
    return $results;
}

$testAddresses = [
    "1600 Amphitheatre Parkway, Mountain View, CA",
    "1 Apple Park Way, Cupertino, CA",
    "One Microsoft Way, Redmond, WA",
    "Invalid Address That Does Not Exist XYZ123",
];

$batchResults = batchGeocodeAddresses($testAddresses, $geocoding);

// ============================================================
// Example 2: Polygon Simplification
// ============================================================

echo "Example 2: Polygon Simplification\n";
echo "==================================\n\n";

// Generate a complex polygon (e.g., from a detailed city boundary)
function generateComplexPolygon(int $vertexCount): array
{
    $vertices = [];
    $centerLat = 37.7749;
    $centerLon = -122.4194;
    $radius = 0.1; // ~11km radius
    
    for ($i = 0; $i < $vertexCount; $i++) {
        $angle = ($i / $vertexCount) * 2 * M_PI;
        // Add some randomness to make it irregular
        $r = $radius * (1 + (rand(-10, 10) / 100));
        $lat = $centerLat + ($r * cos($angle));
        $lon = $centerLon + ($r * sin($angle));
        $vertices[] = new Coordinates($lat, $lon);
    }
    
    return $vertices;
}

$complexVertices = generateComplexPolygon(200); // 200 vertices
echo "Original polygon: " . count($complexVertices) . " vertices\n";

$simplifier = new PolygonSimplifier();

// Simplify with different tolerance levels
$tolerances = [0.0001, 0.0005, 0.001, 0.005];

foreach ($tolerances as $tolerance) {
    $simplified = $simplifier->simplify($complexVertices, $tolerance);
    echo "Tolerance {$tolerance}: " . count($simplified) . " vertices (";
    echo round((1 - count($simplified) / count($complexVertices)) * 100, 2) . "% reduction)\n";
}

// Create a valid polygon (max 100 vertices)
$finalPolygon = $simplifier->simplify($complexVertices, 0.001);

try {
    $polygon = new Polygon($finalPolygon);
    echo "\n✓ Successfully created polygon with " . count($finalPolygon) . " vertices\n\n";
} catch (GeofenceValidationException $e) {
    echo "\n✗ Failed to create polygon: " . $e->getMessage() . "\n\n";
}

// ============================================================
// Example 3: Bearing and Compass Direction
// ============================================================

echo "Example 3: Bearing and Compass Direction\n";
echo "=========================================\n\n";

$bearingCalc = new BearingCalculator();

$cities = [
    'San Francisco' => new Coordinates(37.7749, -122.4194),
    'Los Angeles' => new Coordinates(34.0522, -118.2437),
    'New York' => new Coordinates(40.7128, -74.0060),
    'London' => new Coordinates(51.5074, -0.1278),
    'Tokyo' => new Coordinates(35.6762, 139.6503),
];

echo "Bearings from San Francisco:\n";
foreach ($cities as $name => $coords) {
    if ($name === 'San Francisco') continue;
    
    $bearing = $bearingCalc->calculate($cities['San Francisco'], $coords);
    $distanceCalc = new DistanceCalculator();
    $distance = $distanceCalc->calculate($cities['San Francisco'], $coords);
    
    echo "→ {$name}: {$bearing->degrees}° ({$bearing->direction->value}) - ";
    echo number_format($distance->toKilometers(), 0) . " km\n";
}

echo "\n";

// ============================================================
// Example 4: Travel Time Estimation
// ============================================================

echo "Example 4: Travel Time Estimation\n";
echo "==================================\n\n";

$estimator = new TravelTimeEstimator();

$routes = [
    ['from' => 'SF', 'to' => 'LA', 'speed' => 100, 'unit' => SpeedUnit::KPH],
    ['from' => 'SF', 'to' => 'NY', 'speed' => 65, 'unit' => SpeedUnit::MPH],
    ['from' => 'London', 'to' => 'Tokyo', 'speed' => 900, 'unit' => SpeedUnit::KPH], // Flight
];

foreach ($routes as $route) {
    $from = $cities[$route['from']];
    $to = $cities[$route['to']];
    
    $distanceCalc = new DistanceCalculator();
    $distance = $distanceCalc->calculate($from, $to);
    
    $travelTime = $estimator->estimate($distance, $route['speed'], $route['unit']);
    
    echo "{$route['from']} → {$route['to']}:\n";
    echo "  Distance: " . number_format($distance->toKilometers(), 0) . " km\n";
    echo "  Speed: {$route['speed']} {$route['unit']->value}\n";
    echo "  Travel Time: {$travelTime->hours}h {$travelTime->minutes}m\n\n";
}

// ============================================================
// Example 5: Nearest Warehouse Finder
// ============================================================

echo "Example 5: Nearest Warehouse Finder\n";
echo "====================================\n\n";

function findNearestWarehouse(
    Coordinates $customerLocation,
    array $warehouses,
    DistanceCalculator $calculator
): array {
    $nearestWarehouse = null;
    $shortestDistance = null;
    
    foreach ($warehouses as $name => $coords) {
        $distance = $calculator->calculate($customerLocation, $coords);
        
        if ($shortestDistance === null || $distance->toMeters() < $shortestDistance->toMeters()) {
            $nearestWarehouse = $name;
            $shortestDistance = $distance;
        }
    }
    
    return [
        'warehouse' => $nearestWarehouse,
        'distance' => $shortestDistance,
    ];
}

$warehouses = [
    'San Francisco Warehouse' => new Coordinates(37.7749, -122.4194),
    'Los Angeles Warehouse' => new Coordinates(34.0522, -118.2437),
    'Seattle Warehouse' => new Coordinates(47.6062, -122.3321),
];

$customerLocations = [
    'Customer in Oakland' => new Coordinates(37.8044, -122.2712),
    'Customer in San Diego' => new Coordinates(32.7157, -117.1611),
    'Customer in Portland' => new Coordinates(45.5152, -122.6784),
];

$distanceCalc = new DistanceCalculator();

foreach ($customerLocations as $customerName => $customerCoords) {
    $result = findNearestWarehouse($customerCoords, $warehouses, $distanceCalc);
    
    echo "{$customerName}:\n";
    echo "  Nearest Warehouse: {$result['warehouse']}\n";
    echo "  Distance: " . round($result['distance']->toKilometers(), 2) . " km\n\n";
}

// ============================================================
// Example 6: Dynamic Geofence Validation
// ============================================================

echo "Example 6: Dynamic Geofence Validation\n";
echo "=======================================\n\n";

function validateServiceArea(
    Coordinates $requestLocation,
    array $serviceAreas,
    GeofenceManager $geofence
): array {
    $results = [];
    
    foreach ($serviceAreas as $areaName => $polygon) {
        $isInside = $geofence->contains($requestLocation, $polygon);
        $results[$areaName] = $isInside;
    }
    
    return $results;
}

// Define service areas
$serviceAreas = [
    'Premium Zone' => [
        new Coordinates(37.8049, -122.4194),
        new Coordinates(37.7749, -122.3694),
        new Coordinates(37.7449, -122.4194),
        new Coordinates(37.7749, -122.4694),
    ],
    'Standard Zone' => [
        new Coordinates(37.8549, -122.4194),
        new Coordinates(37.7749, -122.2694),
        new Coordinates(37.6949, -122.4194),
        new Coordinates(37.7749, -122.5694),
    ],
];

$testLocations = [
    'Downtown SF' => new Coordinates(37.7749, -122.4194),
    'Berkeley' => new Coordinates(37.8715, -122.2730),
    'Palo Alto' => new Coordinates(37.4419, -122.1430),
];

$geofenceManager = new GeofenceManager(/* inject repository */);

foreach ($testLocations as $locationName => $locationCoords) {
    echo "{$locationName} ({$locationCoords->latitude}, {$locationCoords->longitude}):\n";
    
    $areaValidation = validateServiceArea($locationCoords, $serviceAreas, $geofenceManager);
    
    foreach ($areaValidation as $area => $isInside) {
        $status = $isInside ? '✓ INSIDE' : '✗ OUTSIDE';
        echo "  {$area}: {$status}\n";
    }
    
    echo "\n";
}

// ============================================================
// Example 7: Route Planning with Multiple Waypoints
// ============================================================

echo "Example 7: Route Planning with Multiple Waypoints\n";
echo "==================================================\n\n";

function planRoute(array $waypoints, DistanceCalculator $calculator): array
{
    $totalDistance = new Distance(0);
    $segments = [];
    
    for ($i = 0; $i < count($waypoints) - 1; $i++) {
        $from = $waypoints[$i];
        $to = $waypoints[$i + 1];
        
        $segmentDistance = $calculator->calculate($from['coords'], $to['coords']);
        $totalDistance = $totalDistance->add($segmentDistance);
        
        $segments[] = [
            'from' => $from['name'],
            'to' => $to['name'],
            'distance_km' => $segmentDistance->toKilometers(),
        ];
    }
    
    return [
        'segments' => $segments,
        'total_distance_km' => $totalDistance->toKilometers(),
    ];
}

$deliveryRoute = [
    ['name' => 'Warehouse', 'coords' => new Coordinates(37.7749, -122.4194)],
    ['name' => 'Customer A', 'coords' => new Coordinates(37.8044, -122.2712)],
    ['name' => 'Customer B', 'coords' => new Coordinates(37.8715, -122.2730)],
    ['name' => 'Customer C', 'coords' => new Coordinates(37.9000, -122.3000)],
    ['name' => 'Warehouse', 'coords' => new Coordinates(37.7749, -122.4194)],
];

$routePlan = planRoute($deliveryRoute, new DistanceCalculator());

echo "Delivery Route:\n";
foreach ($routePlan['segments'] as $index => $segment) {
    echo ($index + 1) . ". {$segment['from']} → {$segment['to']}: ";
    echo round($segment['distance_km'], 2) . " km\n";
}
echo "\nTotal Distance: " . round($routePlan['total_distance_km'], 2) . " km\n";

// Estimate total delivery time (average 40 km/h in city)
$estimator = new TravelTimeEstimator();
$totalTime = $estimator->estimate(
    new Distance($routePlan['total_distance_km'] * 1000),
    40,
    SpeedUnit::KPH
);

echo "Estimated Time: {$totalTime->hours}h {$totalTime->minutes}m\n";
echo "(Assuming 40 km/h average speed in city)\n\n";

// ============================================================
// Example 8: Geofence Performance Monitoring
// ============================================================

echo "Example 8: Geofence Performance Monitoring\n";
echo "===========================================\n\n";

function benchmarkGeofenceChecks(
    array $testPoints,
    array $polygon,
    GeofenceManager $geofence
): void {
    $startTime = microtime(true);
    $iterations = count($testPoints);
    
    foreach ($testPoints as $point) {
        $geofence->contains($point, $polygon);
    }
    
    $endTime = microtime(true);
    $totalTime = ($endTime - $startTime) * 1000; // ms
    $avgTime = $totalTime / $iterations;
    
    echo "Polygon vertices: " . count($polygon) . "\n";
    echo "Test points: {$iterations}\n";
    echo "Total time: " . round($totalTime, 2) . " ms\n";
    echo "Average per check: " . round($avgTime, 4) . " ms\n";
    echo "Throughput: " . number_format(1000 / $avgTime, 0) . " checks/second\n";
}

// Generate test data
$testPolygon = generateComplexPolygon(50);
$testPoints = [];
for ($i = 0; $i < 1000; $i++) {
    $testPoints[] = new Coordinates(
        37.7749 + (rand(-100, 100) / 1000),
        -122.4194 + (rand(-100, 100) / 1000)
    );
}

benchmarkGeofenceChecks($testPoints, $testPolygon, $geofenceManager);

echo "\n=== End of Advanced Examples ===\n";
