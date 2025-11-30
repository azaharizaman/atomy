<?php

/**
 * Basic Usage Example: Nexus Geo Package
 * 
 * This example demonstrates the most common use cases:
 * 1. Geocoding an address to coordinates
 * 2. Reverse geocoding coordinates to an address
 * 3. Calculating distance between two points
 * 4. Checking if a point is within a delivery zone
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Geo\Services\GeocodingManager;
use Nexus\Geo\Services\DistanceCalculator;
use Nexus\Geo\Services\GeofenceManager;
use Nexus\Geo\ValueObjects\Coordinates;

// Assume dependency injection container provides these services
// $geocoding = app(GeocodingManager::class);
// $distance = app(DistanceCalculator::class);
// $geofence = app(GeofenceManager::class);

// ============================================================
// Example 1: Geocode an Address
// ============================================================

echo "Example 1: Geocoding an Address\n";
echo "================================\n\n";

$customerAddress = "1600 Amphitheatre Parkway, Mountain View, CA 94043";

// Geocode the address (with automatic caching)
$result = $geocoding->geocode($customerAddress);

echo "Original Address: {$customerAddress}\n";
echo "Formatted Address: {$result->formattedAddress}\n";
echo "Coordinates: {$result->coordinates->latitude}, {$result->coordinates->longitude}\n";
echo "Provider: {$result->provider}\n"; // 'google' or 'nominatim'
echo "\n";

// ============================================================
// Example 2: Reverse Geocode Coordinates
// ============================================================

echo "Example 2: Reverse Geocoding\n";
echo "=============================\n\n";

$coordinates = new Coordinates(37.4220936, -122.0845853); // Google HQ

$reverseResult = $geocoding->reverseGeocode($coordinates);

echo "Input Coordinates: {$coordinates->latitude}, {$coordinates->longitude}\n";
echo "Address: {$reverseResult->formattedAddress}\n";
echo "\n";

// ============================================================
// Example 3: Calculate Distance Between Two Points
// ============================================================

echo "Example 3: Distance Calculation\n";
echo "===============================\n\n";

$sanFrancisco = new Coordinates(37.7749, -122.4194);
$losAngeles = new Coordinates(34.0522, -118.2437);

$calculator = new DistanceCalculator();
$distance = $calculator->calculate($sanFrancisco, $losAngeles);

echo "From: San Francisco (37.7749, -122.4194)\n";
echo "To: Los Angeles (34.0522, -118.2437)\n";
echo "Distance: {$distance->toKilometers()} km\n";
echo "Distance: {$distance->toMiles()} miles\n";
echo "Distance: {$distance->toMeters()} meters\n";
echo "\n";

// ============================================================
// Example 4: Geofencing (Point in Polygon)
// ============================================================

echo "Example 4: Geofencing\n";
echo "=====================\n\n";

// Define a delivery zone polygon (simplified example)
$deliveryZone = [
    new Coordinates(37.8049, -122.4194), // North
    new Coordinates(37.7749, -122.3694), // East
    new Coordinates(37.7449, -122.4194), // South
    new Coordinates(37.7749, -122.4694), // West
];

$customerLocation1 = new Coordinates(37.7749, -122.4194); // Inside
$customerLocation2 = new Coordinates(37.9000, -122.4194); // Outside

$manager = new GeofenceManager(/* inject repository */);

$isInside1 = $manager->contains($customerLocation1, $deliveryZone);
$isInside2 = $manager->contains($customerLocation2, $deliveryZone);

echo "Delivery Zone vertices: " . count($deliveryZone) . "\n";
echo "Customer 1 (37.7749, -122.4194): " . ($isInside1 ? "INSIDE" : "OUTSIDE") . " zone\n";
echo "Customer 2 (37.9000, -122.4194): " . ($isInside2 ? "INSIDE" : "OUTSIDE") . " zone\n";
echo "\n";

// ============================================================
// Example 5: Complete Delivery Fee Calculation Workflow
// ============================================================

echo "Example 5: Delivery Fee Calculation\n";
echo "====================================\n\n";

function calculateDeliveryFee(
    string $customerAddress,
    Coordinates $warehouseLocation,
    array $deliveryZone,
    GeocodingManager $geocoding,
    DistanceCalculator $distanceCalc,
    GeofenceManager $geofence
): array {
    // Step 1: Geocode customer address
    $geocodeResult = $geocoding->geocode($customerAddress);
    $customerCoords = $geocodeResult->coordinates;
    
    // Step 2: Calculate distance from warehouse
    $distance = $distanceCalc->calculate($warehouseLocation, $customerCoords);
    
    // Step 3: Check if in delivery zone
    $inZone = $geofence->contains($customerCoords, $deliveryZone);
    
    // Step 4: Calculate fee
    // Base fee: $5.00
    // Outside zone: +$0.50 per km
    $baseFee = 5.00;
    $perKmFee = 0.50;
    
    $totalFee = $inZone 
        ? $baseFee 
        : $baseFee + ($distance->toKilometers() * $perKmFee);
    
    return [
        'customer_address' => $geocodeResult->formattedAddress,
        'coordinates' => $customerCoords->toArray(),
        'distance_km' => round($distance->toKilometers(), 2),
        'in_delivery_zone' => $inZone,
        'delivery_fee' => round($totalFee, 2),
        'geocode_provider' => $geocodeResult->provider,
    ];
}

$warehouse = new Coordinates(37.7749, -122.4194); // San Francisco warehouse

$result = calculateDeliveryFee(
    "1600 Amphitheatre Parkway, Mountain View, CA 94043",
    $warehouse,
    $deliveryZone,
    $geocoding,
    $calculator,
    $manager
);

echo "Delivery Fee Calculation:\n";
echo "Address: {$result['customer_address']}\n";
echo "Distance: {$result['distance_km']} km\n";
echo "In Zone: " . ($result['in_delivery_zone'] ? 'Yes' : 'No') . "\n";
echo "Fee: \${$result['delivery_fee']}\n";
echo "Provider: {$result['geocode_provider']}\n";
echo "\n";

// ============================================================
// Example 6: Working with Coordinate Arrays
// ============================================================

echo "Example 6: Coordinate Manipulation\n";
echo "===================================\n\n";

// Convert coordinates to/from arrays for JSON storage
$coords = new Coordinates(37.7749, -122.4194);

// To array
$coordsArray = $coords->toArray();
echo "As array: " . json_encode($coordsArray) . "\n";

// From array
$restoredCoords = Coordinates::fromArray($coordsArray);
echo "Restored: {$restoredCoords->latitude}, {$restoredCoords->longitude}\n";

// Equality check
$coords1 = new Coordinates(37.7749, -122.4194);
$coords2 = new Coordinates(37.7749, -122.4194);
$coords3 = new Coordinates(34.0522, -118.2437);

echo "coords1 equals coords2: " . ($coords1->equals($coords2) ? 'Yes' : 'No') . "\n";
echo "coords1 equals coords3: " . ($coords1->equals($coords3) ? 'Yes' : 'No') . "\n";
echo "\n";

// ============================================================
// Example 7: Cache Management
// ============================================================

echo "Example 7: Cache Management\n";
echo "===========================\n\n";

// First call (cache miss, hits API)
$start = microtime(true);
$result1 = $geocoding->geocode("1600 Amphitheatre Parkway, Mountain View, CA");
$time1 = (microtime(true) - $start) * 1000;

echo "First call (cache miss): {$time1}ms\n";
echo "Provider: {$result1->provider}\n\n";

// Second call (cache hit, instant)
$start = microtime(true);
$result2 = $geocoding->geocode("1600 Amphitheatre Parkway, Mountain View, CA");
$time2 = (microtime(true) - $start) * 1000;

echo "Second call (cache hit): {$time2}ms\n";
echo "Speedup: " . round($time1 / $time2, 2) . "x faster\n\n";

// Clear cache for specific address
$geocoding->clearCache("1600 Amphitheatre Parkway, Mountain View, CA");
echo "Cache cleared for address\n\n";

// Get metrics
$start = new \DateTimeImmutable('2024-01-01');
$end = new \DateTimeImmutable('2024-01-31');
$metrics = $geocoding->getMetrics($start, $end);

echo "Geocoding Metrics (Jan 2024):\n";
echo "Total Requests: {$metrics->totalRequests}\n";
echo "Cache Hits: {$metrics->cacheHits}\n";
echo "Cache Misses: {$metrics->cacheMisses}\n";
echo "API Calls: {$metrics->apiCalls}\n";
echo "Cache Hit Rate: {$metrics->cacheHitRate}%\n";
echo "Estimated Cost: \${$metrics->estimatedCost}\n";

echo "\n=== End of Basic Examples ===\n";
