# Nexus\Geo

Framework-agnostic geographic and location services package for the Nexus ERP system.

## Overview

`Nexus\Geo` provides comprehensive geospatial capabilities including:

- **Geocoding**: Address-to-coordinates conversion with provider abstraction
- **Reverse Geocoding**: Coordinates-to-address lookup
- **Distance Calculations**: Haversine formula-based distance computations
- **Geofencing**: Territory boundary validation
- **Polygon Simplification**: Douglas-Peucker algorithm for optimizing storage
- **Cost Optimization**: 90-day caching with >80% hit rate target
- **Multi-Provider Support**: Google Maps (primary) + OpenStreetMap Nominatim (fallback)

## Features

### Cost-Optimized Geocoding

- **Aggressive Caching**: 90-day TTL with SHA-256 address hashing
- **Provider Chain**: Google Maps for real-time, Nominatim for batch/fallback
- **Metrics Tracking**: Cache hit rate monitoring (target: >80%)
- **Cost Alerting**: Integration with `Nexus\Notifier` for budget thresholds
- **Smart Fallback**: Automatic provider switching on circuit breaker activation

### Geospatial Calculations

- **Distance Calculator**: Uses `league/geotools` Haversine formula
- **Bearing Calculations**: Get direction between two points
- **Destination Points**: Calculate point at distance/bearing from origin
- **Radius Checks**: Fast proximity queries

### Territory Management

- **Polygon Storage**: JSONB format with max 100 vertices
- **Auto-Simplification**: Douglas-Peucker algorithm when exceeding limits
- **Accuracy Tracking**: Loss percentage calculation
- **Geofence Checks**: Point-in-polygon validation

## Installation

```bash
composer require nexus/geo:"*@dev"
```

## Dependencies

### Required Packages

- `geocoder-php/geocoder`: ^4.0 - Multi-provider geocoding abstraction
- `league/geotools`: ^2.0 - Geospatial math library
- `nexus/party`: *@dev - For `PostalAddress` integration
- `nexus/connector`: *@dev - For resilient external API calls
- `psr/log`: ^3.0 - Logging interface

### External Services (Optional)

- **Google Maps Geocoding API**: Primary geocoding provider ($5/1000 requests)
- **OpenStreetMap Nominatim**: Free fallback provider
- **Google Maps Distance Matrix API**: Real-world routing (via `Nexus\Routing`)

## Architecture

This package follows the **Nexus Architecture Principle**: "Logic in Packages, Implementation in Applications."

### Package Layer (Pure PHP)
- **Framework-agnostic**: No Laravel dependencies
- **Business Logic**: All geospatial calculations and geocoding logic
- **Interfaces**: Defines contracts for persistence and external services
- **Value Objects**: Immutable domain objects (Coordinates, Distance, etc.)
- **Services**: GeoManager, RegionManager, GeoCache for orchestration

### Application Layer (Laravel/Atomy)
- **Provider Adapters**: GoogleMapsGeocoder, NominatimGeocoder
- **Repository Implementations**: DbGeoRepository with Eloquent
- **Calculators**: LeagueGeotoolsCalculator implementation
- **Database Migrations**: geo_cache, geo_metrics, regions tables
- **Service Provider**: IoC container bindings
- **API Controllers**: RESTful endpoints for geo operations

## Usage Examples

### 1. Geocode an Address

```php
use Nexus\Geo\Services\GeoManager;
use Nexus\Party\ValueObjects\PostalAddress;

$geoManager = app(GeoManager::class);

$address = new PostalAddress(
    streetLine1: 'Jalan Song',
    city: 'Kuching',
    postalCode: '93350',
    country: 'MYS',
    state: 'Sarawak'
);

$result = $geoManager->geocode($address);

if ($result) {
    echo "Latitude: {$result->getCoordinates()->latitude}\n";
    echo "Longitude: {$result->getCoordinates()->longitude}\n";
    echo "Confidence: {$result->getConfidenceScore()}%\n";
    echo "Provider: {$result->getProviderName()}\n";
}
```

### 2. Calculate Distance Between Two Points

```php
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\DistanceUnit;

$calculator = app(DistanceCalculatorInterface::class);

$origin = new Coordinates(1.5535, 110.3593); // Kuching
$destination = new Coordinates(4.8921, 114.9421); // Miri

$distance = $calculator->calculateDistance($origin, $destination, DistanceUnit::KILOMETERS);

echo "Distance: {$distance->getValue()} km\n";
```

### 3. Check if Point is Within Service Territory

```php
use Nexus\Geo\Services\RegionManager;

$regionManager = app(RegionManager::class);

$customerLocation = new Coordinates(1.5535, 110.3593);

if ($regionManager->isPointInRegion($customerLocation, 'kuching-service-area')) {
    echo "Customer is within service area\n";
} else {
    echo "Customer is outside service area\n";
}
```

### 4. Monitor Geocoding Costs

```php
use Nexus\Geo\Services\GeoManager;

$geoManager = app(GeoManager::class);
$metrics = $geoManager->getMetrics();

echo "Cache Hit Rate: {$metrics->getHitRatePercentage()}%\n";
echo "Google API Calls: {$metrics->getGoogleCallCount()}\n";
echo "Nominatim Calls: {$metrics->getNominatimCallCount()}\n";
echo "Estimated Daily Cost: \${$metrics->getEstimatedDailyCost()}\n";

// Check if alert threshold breached
if ($metrics->getHitRatePercentage() < 80) {
    // Alert sent automatically via Nexus\Notifier
    echo "WARNING: Cache hit rate below target!\n";
}
```

### 5. Simplify Complex Polygon

```php
use Nexus\Geo\Services\RegionManager;

$regionManager = app(RegionManager::class);

$complexPolygon = [
    // 250 vertices from GeoJSON import
];

$simplificationResult = $regionManager->validatePolygonComplexity($complexPolygon);

echo "Original Vertices: {$simplificationResult->getOriginalVertexCount()}\n";
echo "Simplified Vertices: {$simplificationResult->getSimplifiedVertexCount()}\n";
echo "Accuracy Loss: {$simplificationResult->getAccuracyLossPercentage()}%\n";

// Auto-simplify if needed
if ($simplificationResult->getOriginalVertexCount() > 100) {
    $region->setBoundary($simplificationResult->getSimplifiedBoundary());
}
```

## Cost Optimization Strategies

### 1. Aggressive Caching

- **90-Day TTL**: Addresses rarely change, long cache duration reduces API calls
- **SHA-256 Hashing**: Prevents duplicates from formatting variations
- **Deduplication**: Multiple users entering same address hits cache

### 2. Provider Failover

- **Google Maps**: High accuracy, paid service ($5/1000 requests)
- **Nominatim**: Lower accuracy, free service
- **Smart Routing**: Real-time → Google, Batch jobs → Nominatim

### 3. Batch Processing

- Import historical addresses during off-peak hours using free provider
- Pre-geocode known locations (offices, warehouses, frequent customers)
- Queue non-urgent geocoding for async processing

### 4. Monitoring & Alerting

- **Target**: >80% cache hit rate
- **Alerts**: Sent via `Nexus\Notifier` when:
  - Hit rate drops below 80%
  - Daily cost exceeds budget threshold
  - Provider experiencing high error rate

## Polygon Simplification

### Douglas-Peucker Algorithm

The package uses the Douglas-Peucker algorithm to reduce polygon complexity while preserving shape:

```php
use Nexus\Geo\Contracts\PolygonSimplifierInterface;

$simplifier = app(PolygonSimplifierInterface::class);

$vertices = [ /* 250 coordinate pairs */ ];
$tolerance = 0.0001; // ~11 meters

$simplified = $simplifier->simplify($vertices, $tolerance);
```

### Recommended Tolerances

| Use Case | Tolerance | Accuracy | Typical Reduction |
|----------|-----------|----------|-------------------|
| City boundaries | 0.001 | ~111m | 60-70% |
| Service territories | 0.0001 | ~11m | 40-50% |
| Precise geofencing | 0.00001 | ~1.1m | 20-30% |

### Storage Limits

- **Max Vertices**: 100 per polygon (enforced)
- **Auto-Simplification**: Triggered when import exceeds limit
- **Visual Diff**: Admin UI shows accuracy loss overlay

## Cache Metrics Interpretation

### Hit Rate Analysis

| Hit Rate | Status | Action Required |
|----------|--------|-----------------|
| >90% | Excellent | Monitor costs, increase cache TTL if stable |
| 80-90% | Good | Target range, no action |
| 70-80% | Warning | Review address quality, check for retry loops |
| <70% | Critical | Investigate cache failures, verify cache is enabled |

### Cost Projections

```php
// Example: Calculate monthly cost
$metrics = $geoManager->getMetrics();
$dailyCost = $metrics->getEstimatedDailyCost();
$monthlyCost = $dailyCost * 30;

if ($monthlyCost > $budgetLimit) {
    // Increase cache TTL or switch to Nominatim for batch jobs
}
```

## Provider Failover Behavior

### Circuit Breaker Integration

The package integrates with `Nexus\Connector` for resilient external API calls:

1. **Google Maps Primary**: Attempts geocoding via Google Maps API
2. **Circuit Breaker**: Monitors failure rate (5 failures in 60 seconds)
3. **Fallback Trigger**: If circuit opens, switches to Nominatim
4. **Recovery**: Circuit half-open after 30 seconds, full recovery after 2 successful calls

### Configuration

```php
// config/geo.php
return [
    'providers' => [
        [
            'name' => 'google',
            'priority' => 1,
            'for' => 'realtime', // Use for real-time geocoding
        ],
        [
            'name' => 'nominatim',
            'priority' => 2,
            'for' => 'batch', // Use for batch processing
        ],
    ],
    'cache_hit_threshold' => 80, // Alert if below 80%
    'daily_budget_limit' => 100.00, // USD
];
```

## Integration with Nexus\Party

### PostalAddress Coordinates

The package extends `Nexus\Party\ValueObjects\PostalAddress` to support optional geocoding:

```php
use Nexus\Party\ValueObjects\PostalAddress;
use Nexus\Geo\ValueObjects\Coordinates;

// Create address without coordinates
$address = new PostalAddress(/* ... */);

// Geocode and attach coordinates
$result = $geoManager->geocode($address);
$addressWithCoords = $address->withCoordinates($result->getCoordinates());

// Check if needs geocoding
if ($address->needsGeocoding()) {
    // Queue geocoding job
}
```

## Migration from Google-Only to Hybrid Setup

### Step 1: Enable Nominatim Fallback

```php
// config/geo.php
'providers' => [
    ['name' => 'google', 'priority' => 1, 'for' => 'realtime'],
    ['name' => 'nominatim', 'priority' => 2, 'for' => 'batch'], // Add this
],
```

### Step 2: Batch Re-Geocode with Nominatim

```bash
php artisan geo:batch-geocode --provider=nominatim --limit=1000
```

### Step 3: Monitor Cost Reduction

```bash
php artisan geo:metrics --period=30days
```

## Testing

```bash
# Run package tests (framework-agnostic)
cd packages/Geo
composer test

# Run Atomy integration tests
cd apps/Atomy
php artisan test --filter=Geo
```

## Performance Benchmarks

| Operation | Target | Typical |
|-----------|--------|---------|
| Cache lookup | <5ms | 2-3ms |
| Geocoding (cache hit) | <10ms | 5-8ms |
| Geocoding (Google API) | <500ms | 200-400ms |
| Geocoding (Nominatim) | <1000ms | 600-900ms |
| Distance calculation | <5ms | 1-2ms |
| Polygon simplification (100 vertices) | <20ms | 10-15ms |
| Geofence check | <10ms | 3-5ms |

## Documentation

Comprehensive documentation is available in the `docs/` directory:

### Getting Started
- **[Getting Started Guide](docs/getting-started.md)** - Prerequisites, installation, core concepts, and your first integration
- **[Quick Start](docs/getting-started.md#your-first-integration)** - Complete working example

### API Reference
- **[API Reference](docs/api-reference.md)** - Complete interface and method documentation
  - All 7 interfaces (GeoRepositoryInterface, GeocoderInterface, GeofenceInterface, etc.)
  - All 6 services (GeocodingManager, DistanceCalculator, BearingCalculator, etc.)
  - All 8 value objects (Coordinates, Distance, BearingResult, Polygon, etc.)
  - All 5 exceptions with named constructors

### Integration Guides
- **[Integration Guide](docs/integration-guide.md)** - Framework-specific implementation examples
  - Laravel integration (complete with migrations, repositories, service providers)
  - Symfony integration (Doctrine entities, services.yaml configuration)
  - Common patterns (batch geocoding, delivery zones, nearest warehouse)

### Code Examples
- **[Basic Usage](docs/examples/basic-usage.php)** - Essential operations
  - Geocoding and reverse geocoding
  - Distance calculations
  - Geofencing (point-in-polygon)
  - Complete delivery fee workflow
- **[Advanced Usage](docs/examples/advanced-usage.php)** - Complex scenarios
  - Batch geocoding with rate limiting
  - Polygon simplification
  - Bearing and compass direction
  - Travel time estimation
  - Multi-warehouse routing
  - Dynamic geofence validation

### Implementation Details
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Project metrics and progress tracking
- **[Requirements](REQUIREMENTS.md)** - 35 tracked requirements across 9 categories
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Test coverage and strategy (target: 95%)
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package financial analysis ($46,848 value, 681% ROI)

### Quick Links
| What You Need | Where to Look |
|---------------|---------------|
| First-time setup | [Getting Started Guide](docs/getting-started.md) |
| Method signatures | [API Reference](docs/api-reference.md) |
| Laravel example | [Integration Guide - Laravel](docs/integration-guide.md#laravel-integration) |
| Symfony example | [Integration Guide - Symfony](docs/integration-guide.md#symfony-integration) |
| Working code | [Basic Usage Examples](docs/examples/basic-usage.php) |
| Complex patterns | [Advanced Usage Examples](docs/examples/advanced-usage.php) |

## License

MIT License. See [LICENSE](LICENSE) for details.

## Contributing

Follow Nexus architecture principles:

1. Keep the package framework-agnostic
2. Define all dependencies via interfaces
3. Use immutable Value Objects for domain concepts
4. Place all business logic in services
5. No database access or migrations in this package

## Support

For issues, questions, or contributions, please refer to the main Nexus monorepo documentation.
