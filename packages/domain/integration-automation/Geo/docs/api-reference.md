# API Reference: Nexus Geo

**Package:** `Nexus\Geo`  
**Namespace:** `Nexus\Geo`

---

## Table of Contents

- [Interfaces](#interfaces)
  - [GeoRepositoryInterface](#georepositoryinterface)
  - [GeocoderInterface](#geocoderinterface)
  - [GeofenceInterface](#geofenceinterface)
- [Services](#services)
  - [GeocodingManager](#geocodingmanager)
  - [DistanceCalculator](#distancecalculator)
  - [BearingCalculator](#bearingcalculator)
  - [GeofenceManager](#geofencemanager)
  - [PolygonSimplifier](#polygonsimplifier)
  - [TravelTimeEstimator](#traveltimeestimator)
- [Value Objects](#value-objects)
  - [Coordinates](#coordinates)
  - [Distance](#distance)
  - [BearingResult](#bearingresult)
  - [Polygon](#polygon)
  - [GeocodeResult](#geocoderesult)
  - [GeoMetrics](#geometrics)
  - [TravelTimeResult](#traveltimeresult)
  - [GeofenceCheckResult](#geofencecheckresult)
- [Enums](#enums)
  - [DistanceUnit](#distanceunit)
  - [SpeedUnit](#speedunit)
  - [CompassDirection](#compassdirection)
- [Exceptions](#exceptions)
  - [GeoException](#geoexception)
  - [InvalidCoordinatesException](#invalidcoordinatesexception)
  - [GeocodingFailedException](#geocodingfailedexception)
  - [GeofenceValidationException](#geofencevalidationexception)
  - [PolygonComplexityException](#polygoncomplexityexception)

---

## Interfaces

### GeoRepositoryInterface

**Purpose:** Define data persistence needs for geocoding cache and metrics.

**Location:** `src/Contracts/GeoRepositoryInterface.php`

```php
namespace Nexus\Geo\Contracts;

interface GeoRepositoryInterface
{
    /**
     * Find cached geocode result for an address.
     *
     * @param string $tenantId Tenant identifier for multi-tenancy
     * @param string $address Address string to lookup
     * @return GeocodeResult|null Cached result or null if not found/expired
     */
    public function findCachedGeocode(string $tenantId, string $address): ?GeocodeResult;

    /**
     * Cache a geocode result.
     *
     * @param string $tenantId Tenant identifier
     * @param GeocodeResult $result Geocode result to cache
     * @param int $ttlDays Time-to-live in days (default: 90)
     * @return void
     */
    public function cacheGeocode(string $tenantId, GeocodeResult $result, int $ttlDays = 90): void;

    /**
     * Get geocoding metrics for a tenant within a date range.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $start Start date
     * @param \DateTimeImmutable $end End date
     * @return GeoMetrics Metrics including cache hit rate, request counts
     */
    public function getMetrics(
        string $tenantId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): GeoMetrics;
}
```

**Consumer Responsibility:**
- Implement database storage for geocoding cache (recommended table: `geo_cache`)
- Handle cache expiration (TTL) logic
- Calculate metrics (cache hit rate, API call counts, cost tracking)

**Example Implementation:**
```php
// See docs/getting-started.md for complete example
class DatabaseGeoRepository implements GeoRepositoryInterface { /* ... */ }
```

---

### GeocoderInterface

**Purpose:** Abstract external geocoding API providers.

**Location:** `src/Contracts/GeocoderInterface.php`

```php
namespace Nexus\Geo\Contracts;

interface GeocoderInterface
{
    /**
     * Geocode an address to coordinates (forward geocoding).
     *
     * @param string $address Full address string
     * @return GeocodeResult Coordinates and formatted address
     * @throws GeocodingFailedException When geocoding fails
     */
    public function geocode(string $address): GeocodeResult;

    /**
     * Reverse geocode coordinates to an address.
     *
     * @param Coordinates $coordinates Geographic coordinates
     * @return GeocodeResult Address at the given coordinates
     * @throws GeocodingFailedException When reverse geocoding fails
     */
    public function reverseGeocode(Coordinates $coordinates): GeocodeResult;
}
```

**Consumer Responsibility:**
- Integrate with geocoding API providers (Google Maps, Nominatim, etc.)
- Handle API errors and implement failover logic
- Manage API credentials and rate limiting

**Example Implementation:**
```php
// See docs/getting-started.md for complete example
class GoogleMapsGeocoder implements GeocoderInterface { /* ... */ }
```

---

### GeofenceInterface

**Purpose:** Define geofencing data persistence needs.

**Location:** `src/Contracts/GeofenceInterface.php`

```php
namespace Nexus\Geo\Contracts;

interface GeofenceInterface
{
    /**
     * Get all geofences for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @return array<Polygon> Array of geofence polygons
     */
    public function getAllGeofences(string $tenantId): array;

    /**
     * Find geofence by identifier.
     *
     * @param string $geofenceId Geofence identifier
     * @return Polygon|null Polygon or null if not found
     */
    public function findGeofenceById(string $geofenceId): ?Polygon;

    /**
     * Save a geofence polygon.
     *
     * @param string $tenantId Tenant identifier
     * @param string $geofenceId Geofence identifier
     * @param Polygon $polygon Polygon defining the geofence
     * @return void
     */
    public function saveGeofence(string $tenantId, string $geofenceId, Polygon $polygon): void;
}
```

**Consumer Responsibility:**
- Store geofence polygons in database (JSONB recommended)
- Validate polygon vertex count (max 100 vertices)
- Handle multi-tenancy scoping

---

## Services

### GeocodingManager

**Purpose:** Primary service for geocoding operations with caching and failover.

**Location:** `src/Services/GeocodingManager.php`

**Constructor:**
```php
public function __construct(
    private readonly GeoRepositoryInterface $repository,
    private readonly GeocoderInterface $geocoder,
    private readonly TenantContextInterface $tenantContext
)
```

**Methods:**

#### `geocode(string $address): GeocodeResult`

Geocode an address to coordinates with automatic caching.

**Algorithm:**
1. Check cache for existing result
2. If cache miss, call external provider
3. Store result in cache (90-day TTL)
4. Return GeocodeResult

**Parameters:**
- `$address` - Full address string (e.g., "1600 Amphitheatre Parkway, Mountain View, CA 94043")

**Returns:** `GeocodeResult` containing coordinates, formatted address, provider name

**Throws:** `GeocodingFailedException` when all providers fail

**Example:**
```php
$manager = app(GeocodingManager::class);
$result = $manager->geocode("1600 Amphitheatre Parkway, Mountain View, CA");

echo "Lat: {$result->coordinates->latitude}\n";
echo "Provider: {$result->provider}\n"; // "google" or "nominatim"
```

#### `reverseGeocode(Coordinates $coordinates): GeocodeResult`

Reverse geocode coordinates to an address.

**Parameters:**
- `$coordinates` - Coordinates value object

**Returns:** `GeocodeResult` with formatted address

**Example:**
```php
$coords = new Coordinates(37.4220936, -122.0845853);
$result = $manager->reverseGeocode($coords);
echo "Address: {$result->formattedAddress}\n";
```

#### `clearCache(string $address): void`

Manually invalidate cache for a specific address.

**Parameters:**
- `$address` - Address to remove from cache

**Example:**
```php
$manager->clearCache("123 Main St, Springfield");
```

#### `getMetrics(\DateTimeImmutable $start, \DateTimeImmutable $end): GeoMetrics`

Get geocoding metrics for current tenant.

**Parameters:**
- `$start` - Start date
- `$end` - End date

**Returns:** `GeoMetrics` with cache hit rate, request counts, estimated cost

**Example:**
```php
$start = new \DateTimeImmutable('2024-01-01');
$end = new \DateTimeImmutable('2024-01-31');
$metrics = $manager->getMetrics($start, $end);

echo "Cache hit rate: {$metrics->cacheHitRate}%\n";
echo "API calls: {$metrics->apiCalls}\n";
echo "Estimated cost: ${$metrics->estimatedCost}\n";
```

---

### DistanceCalculator

**Purpose:** Calculate geographic distances using Haversine formula.

**Location:** `src/Services/DistanceCalculator.php`

**Constructor:**
```php
public function __construct()
// No dependencies
```

**Methods:**

#### `calculate(Coordinates $from, Coordinates $to): Distance`

Calculate great-circle distance between two points.

**Algorithm:** Haversine formula (assumes spherical Earth)

**Accuracy:** ~99.5% (within 0.5% of true ellipsoidal distance)

**Parameters:**
- `$from` - Starting coordinates
- `$to` - Destination coordinates

**Returns:** `Distance` value object

**Example:**
```php
$calculator = new DistanceCalculator();

$sanFrancisco = new Coordinates(37.7749, -122.4194);
$losAngeles = new Coordinates(34.0522, -118.2437);

$distance = $calculator->calculate($sanFrancisco, $losAngeles);

echo $distance->toKilometers(); // 559.12
echo $distance->toMiles();      // 347.42
echo $distance->toMeters();     // 559120.45
```

---

### BearingCalculator

**Purpose:** Calculate bearing and compass direction between two points.

**Location:** `src/Services/BearingCalculator.php`

**Methods:**

#### `calculate(Coordinates $from, Coordinates $to): BearingResult`

Calculate initial bearing (forward azimuth) from point A to point B.

**Algorithm:** Spherical trigonometry

**Parameters:**
- `$from` - Starting coordinates
- `$to` - Destination coordinates

**Returns:** `BearingResult` with degrees and compass direction

**Example:**
```php
$calculator = new BearingCalculator();

$from = new Coordinates(51.5074, -0.1278);  // London
$to = new Coordinates(40.7128, -74.0060);   // New York

$bearing = $calculator->calculate($from, $to);

echo $bearing->degrees;    // 288.34 (degrees from north)
echo $bearing->direction->value; // "NW" (CompassDirection enum)
```

---

### GeofenceManager

**Purpose:** Check if coordinates are within defined geographic boundaries.

**Location:** `src/Services/GeofenceManager.php`

**Constructor:**
```php
public function __construct(
    private readonly GeofenceInterface $geofenceRepository
)
```

**Methods:**

#### `contains(Coordinates $point, array $polygon): bool`

Check if a point is inside a polygon using ray-casting algorithm.

**Algorithm:** Ray-casting (point-in-polygon test)

**Parameters:**
- `$point` - Coordinates to test
- `$polygon` - Array of Coordinates defining the boundary (minimum 3 vertices)

**Returns:** `true` if point is inside polygon, `false` otherwise

**Example:**
```php
$manager = app(GeofenceManager::class);

$deliveryZone = [
    new Coordinates(37.7749, -122.4194),
    new Coordinates(37.7849, -122.4094),
    new Coordinates(37.7649, -122.4094),
];

$customerLocation = new Coordinates(37.7749, -122.4144);

if ($manager->contains($customerLocation, $deliveryZone)) {
    echo "Within delivery zone\n";
}
```

#### `check(Coordinates $point, string $geofenceId): GeofenceCheckResult`

Check if a point is within a stored geofence.

**Parameters:**
- `$point` - Coordinates to test
- `$geofenceId` - Geofence identifier

**Returns:** `GeofenceCheckResult` with boolean result and metadata

**Example:**
```php
$result = $manager->check($customerLocation, 'delivery-zone-west');
echo "Inside: " . ($result->isInside ? 'Yes' : 'No') . "\n";
echo "Geofence: {$result->geofenceName}\n";
```

---

### PolygonSimplifier

**Purpose:** Simplify polygons using Douglas-Peucker algorithm for efficient storage.

**Location:** `src/Services/PolygonSimplifier.php`

**Methods:**

#### `simplify(array $polygon, float $tolerance = 0.0001): array`

Reduce polygon vertex count while maintaining shape.

**Algorithm:** Douglas-Peucker (recursive line simplification)

**Parameters:**
- `$polygon` - Array of Coordinates (minimum 3 vertices)
- `$tolerance` - Maximum distance deviation (default: 0.0001 degrees ≈ 11 meters)

**Returns:** Simplified array of Coordinates (maximum 100 vertices)

**Throws:** `PolygonComplexityException` if simplified polygon still exceeds 100 vertices

**Example:**
```php
$simplifier = new PolygonSimplifier();

// Original polygon with 500 vertices
$complexPolygon = [...]; // 500 Coordinates objects

// Simplify to ~50 vertices
$simplified = $simplifier->simplify($complexPolygon, tolerance: 0.001);

echo "Original: " . count($complexPolygon) . " vertices\n";  // 500
echo "Simplified: " . count($simplified) . " vertices\n";    // ~50
```

---

### TravelTimeEstimator

**Purpose:** Estimate travel time based on distance and average speed.

**Location:** `src/Services/TravelTimeEstimator.php`

**Methods:**

#### `estimate(Distance $distance, float $averageSpeed, SpeedUnit $unit): TravelTimeResult`

Estimate travel time for a given distance and speed.

**Formula:** `time = distance / speed`

**Parameters:**
- `$distance` - Distance value object
- `$averageSpeed` - Average speed (numeric value)
- `$unit` - Speed unit (SpeedUnit enum: KPH, MPH)

**Returns:** `TravelTimeResult` with hours, minutes, seconds

**Example:**
```php
$estimator = new TravelTimeEstimator();

$distance = new Distance(559120.45); // 559 km in meters
$result = $estimator->estimate($distance, 60.0, SpeedUnit::KPH);

echo "Travel time: {$result->hours}h {$result->minutes}m\n"; // 9h 19m
```

---

## Value Objects

### Coordinates

**Purpose:** Immutable geographic coordinates (latitude/longitude).

**Location:** `src/ValueObjects/Coordinates.php`

**Properties:**
```php
public readonly float $latitude;   // -90 to 90
public readonly float $longitude;  // -180 to 180
```

**Constructor:**
```php
public function __construct(
    float $latitude,
    float $longitude
)
```

**Validation:**
- Latitude must be between -90 and 90 (inclusive)
- Longitude must be between -180 and 180 (inclusive)

**Throws:** `InvalidCoordinatesException` on validation failure

**Methods:**

#### `toArray(): array`
```php
$coords = new Coordinates(37.7749, -122.4194);
$array = $coords->toArray();
// ['latitude' => 37.7749, 'longitude' => -122.4194]
```

#### `equals(Coordinates $other): bool`
```php
$coords1 = new Coordinates(37.7749, -122.4194);
$coords2 = new Coordinates(37.7749, -122.4194);
$coords1->equals($coords2); // true
```

#### `static fromArray(array $data): self`
```php
$coords = Coordinates::fromArray([
    'latitude' => 37.7749,
    'longitude' => -122.4194
]);
```

---

### Distance

**Purpose:** Immutable distance value with unit conversions.

**Location:** `src/ValueObjects/Distance.php`

**Properties:**
```php
private readonly float $meters; // Internal storage in meters
```

**Constructor:**
```php
public function __construct(float $meters)
```

**Methods:**

#### `toMeters(): float`
```php
$distance = new Distance(1000);
echo $distance->toMeters(); // 1000.0
```

#### `toKilometers(): float`
```php
echo $distance->toKilometers(); // 1.0
```

#### `toMiles(): float`
```php
echo $distance->toMiles(); // 0.621371
```

#### `toNauticalMiles(): float`
```php
echo $distance->toNauticalMiles(); // 0.539957
```

#### `add(Distance $other): Distance`
```php
$d1 = new Distance(1000); // 1 km
$d2 = new Distance(500);  // 0.5 km
$total = $d1->add($d2);
echo $total->toKilometers(); // 1.5
```

---

### BearingResult

**Purpose:** Bearing angle and compass direction.

**Location:** `src/ValueObjects/BearingResult.php`

**Properties:**
```php
public readonly float $degrees;                // 0-360
public readonly CompassDirection $direction;   // N, NE, E, SE, S, SW, W, NW
```

**Example:**
```php
$bearing = new BearingResult(45.0, CompassDirection::NE);
echo "Heading: {$bearing->degrees}° {$bearing->direction->value}\n";
// "Heading: 45.0° NE"
```

---

### Polygon

**Purpose:** Geographic polygon (boundary) with vertex validation.

**Location:** `src/ValueObjects/Polygon.php`

**Properties:**
```php
/** @var array<Coordinates> */
public readonly array $vertices;
```

**Constructor:**
```php
public function __construct(array $vertices)
```

**Validation:**
- Minimum 3 vertices
- Maximum 100 vertices (enforced for JSONB storage efficiency)

**Throws:** `GeofenceValidationException` on validation failure

**Methods:**

#### `toArray(): array`
```php
$polygon = new Polygon([...]);
$array = $polygon->toArray();
// [['latitude' => 37.7749, 'longitude' => -122.4194], ...]
```

---

### GeocodeResult

**Purpose:** Result of geocoding operation.

**Location:** `src/ValueObjects/GeocodeResult.php`

**Properties:**
```php
public readonly Coordinates $coordinates;
public readonly string $formattedAddress;
public readonly string $provider;    // "google", "nominatim"
public readonly string $address;     // Original input address
```

**Constructor:**
```php
public function __construct(
    Coordinates $coordinates,
    string $formattedAddress,
    string $provider,
    string $address
)
```

---

### GeoMetrics

**Purpose:** Geocoding performance metrics.

**Location:** `src/ValueObjects/GeoMetrics.php`

**Properties:**
```php
public readonly int $totalRequests;
public readonly int $cacheHits;
public readonly int $cacheMisses;
public readonly int $apiCalls;
public readonly float $cacheHitRate;     // Percentage
public readonly float $estimatedCost;    // USD
```

---

### TravelTimeResult

**Purpose:** Estimated travel time.

**Location:** `src/ValueObjects/TravelTimeResult.php`

**Properties:**
```php
public readonly int $totalSeconds;
public readonly int $hours;
public readonly int $minutes;
public readonly int $seconds;
```

---

### GeofenceCheckResult

**Purpose:** Result of geofence containment check.

**Location:** `src/ValueObjects/GeofenceCheckResult.php`

**Properties:**
```php
public readonly bool $isInside;
public readonly string $geofenceId;
public readonly string $geofenceName;
```

---

## Enums

### DistanceUnit

**Purpose:** Distance measurement units.

**Location:** `src/Enums/DistanceUnit.php`

```php
enum DistanceUnit: string
{
    case METERS = 'm';
    case KILOMETERS = 'km';
    case MILES = 'mi';
    case NAUTICAL_MILES = 'nmi';
}
```

---

### SpeedUnit

**Purpose:** Speed measurement units.

**Location:** `src/Enums/SpeedUnit.php`

```php
enum SpeedUnit: string
{
    case KPH = 'km/h';  // Kilometers per hour
    case MPH = 'mph';   // Miles per hour
}
```

---

### CompassDirection

**Purpose:** Cardinal and intercardinal directions.

**Location:** `src/Enums/CompassDirection.php`

```php
enum CompassDirection: string
{
    case N = 'N';      // North (337.5° - 22.5°)
    case NE = 'NE';    // Northeast (22.5° - 67.5°)
    case E = 'E';      // East (67.5° - 112.5°)
    case SE = 'SE';    // Southeast (112.5° - 157.5°)
    case S = 'S';      // South (157.5° - 202.5°)
    case SW = 'SW';    // Southwest (202.5° - 247.5°)
    case W = 'W';      // West (247.5° - 292.5°)
    case NW = 'NW';    // Northwest (292.5° - 337.5°)
}
```

**Methods:**

#### `static fromDegrees(float $degrees): self`
```php
$direction = CompassDirection::fromDegrees(45.0); // CompassDirection::NE
```

---

## Exceptions

### GeoException

**Base exception for all geo-related errors.**

**Location:** `src/Exceptions/GeoException.php`

```php
class GeoException extends \Exception
{
    public static function withMessage(string $message): self;
}
```

---

### InvalidCoordinatesException

**Thrown when coordinates are out of valid range.**

**Location:** `src/Exceptions/InvalidCoordinatesException.php`

```php
class InvalidCoordinatesException extends GeoException
{
    public static function invalidLatitude(float $latitude): self;
    public static function invalidLongitude(float $longitude): self;
}
```

**Example:**
```php
try {
    $coords = new Coordinates(91.0, 0.0); // Invalid latitude
} catch (InvalidCoordinatesException $e) {
    echo $e->getMessage(); // "Invalid latitude: 91.0. Must be between -90 and 90."
}
```

---

### GeocodingFailedException

**Thrown when geocoding operation fails.**

**Location:** `src/Exceptions/GeocodingFailedException.php`

```php
class GeocodingFailedException extends GeoException
{
    public static function forAddress(string $address, string $reason): self;
    public static function allProvidersFailed(string $address): self;
}
```

---

### GeofenceValidationException

**Thrown when geofence polygon is invalid.**

**Location:** `src/Exceptions/GeofenceValidationException.php`

```php
class GeofenceValidationException extends GeoException
{
    public static function tooFewVertices(int $count): self;
    public static function tooManyVertices(int $count): self;
}
```

---

### PolygonComplexityException

**Thrown when polygon exceeds maximum vertex count.**

**Location:** `src/Exceptions/PolygonComplexityException.php`

```php
class PolygonComplexityException extends GeoException
{
    public static function exceededMaxVertices(int $count, int $max): self;
}
```

**Example:**
```php
try {
    $polygon = new Polygon($vertices); // 150 vertices
} catch (GeofenceValidationException $e) {
    // Simplify polygon
    $simplified = $simplifier->simplify($vertices, tolerance: 0.001);
    $polygon = new Polygon($simplified);
}
```

---

## Complete Usage Example

```php
<?php

use Nexus\Geo\Services\GeocodingManager;
use Nexus\Geo\Services\DistanceCalculator;
use Nexus\Geo\Services\GeofenceManager;
use Nexus\Geo\ValueObjects\Coordinates;

// Inject services via dependency injection
public function __construct(
    private readonly GeocodingManager $geocoding,
    private readonly DistanceCalculator $distance,
    private readonly GeofenceManager $geofence
) {}

public function processDeliveryOrder(string $orderId): void
{
    // 1. Geocode customer address
    $order = Order::find($orderId);
    $result = $this->geocoding->geocode($order->delivery_address);
    $customerLocation = $result->coordinates;
    
    // 2. Calculate distance from warehouse
    $warehouse = new Coordinates(37.7749, -122.4194);
    $distance = $this->distance->calculate($warehouse, $customerLocation);
    
    // 3. Check if within delivery zone
    $deliveryZone = [...]; // Array of Coordinates
    $isInZone = $this->geofence->contains($customerLocation, $deliveryZone);
    
    // 4. Update order
    $order->delivery_distance_km = $distance->toKilometers();
    $order->is_in_zone = $isInZone;
    $order->save();
    
    logger()->info('Delivery processed', [
        'order_id' => $orderId,
        'distance_km' => $distance->toKilometers(),
        'in_zone' => $isInZone,
        'geocode_provider' => $result->provider,
    ]);
}
```

---

**For more examples, see:**
- [Getting Started Guide](getting-started.md)
- [Integration Guide](integration-guide.md)
- [Code Examples](examples/)
