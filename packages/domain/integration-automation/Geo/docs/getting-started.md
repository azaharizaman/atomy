# Getting Started with Nexus Geo

## Prerequisites

- PHP 8.3 or higher
- Composer
- Google Maps API key (optional, for primary geocoding provider)
- Understanding of geographic coordinates (latitude/longitude)

## Installation

```bash
composer require nexus/geo:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Geocoding customer/vendor addresses to coordinates
- ✅ Calculating distances between locations (delivery routes, service areas)
- ✅ Geofencing (checking if points are within defined regions)
- ✅ Polygon simplification for efficient storage (JSONB columns)
- ✅ Travel time estimation
- ✅ Bearing/compass direction calculations

Do NOT use this package for:
- ❌ Route optimization (use `Nexus\Routing` package)
- ❌ Turn-by-turn navigation
- ❌ Real-time traffic data
- ❌ Elevation/terrain analysis

## Core Concepts

### Concept 1: Coordinates
Geographic coordinates are represented as latitude/longitude pairs with decimal(10,7) precision (11mm accuracy).

```php
use Nexus\Geo\ValueObjects\Coordinates;

$coords = new Coordinates(
    latitude: 37.7749,    // -90 to 90
    longitude: -122.4194  // -180 to 180
);
```

### Concept 2: Cache-First Geocoding
To minimize API costs, geocoding results are cached for 90 days. The system automatically:
1. Checks cache first
2. Calls primary provider (Google Maps) if cache miss
3. Falls back to secondary provider (Nominatim) if primary fails
4. Stores result in cache for future requests

### Concept 3: Provider Failover
The package uses a circuit breaker pattern (via `Nexus\Connector`) to automatically switch providers:
- **Primary:** Google Maps Geocoding API ($5/1000 requests)
- **Fallback:** OpenStreetMap Nominatim (free)

### Concept 4: Polygon Simplification
Boundary polygons are limited to 100 vertices for efficient JSONB storage. The Douglas-Peucker algorithm simplifies complex polygons while maintaining shape accuracy.

## Basic Configuration

### Step 1: Implement Required Interfaces

The package defines contracts that your application must implement:

#### GeoRepositoryInterface (Cache Storage)

```php
namespace App\Repositories;

use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Nexus\Geo\ValueObjects\GeoMetrics;

final readonly class DatabaseGeoRepository implements GeoRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db
    ) {}
    
    public function findCachedGeocode(string $tenantId, string $address): ?GeocodeResult
    {
        // Query geo_cache table
        $row = $this->db->query(
            'SELECT * FROM geo_cache WHERE tenant_id = ? AND address = ? AND expires_at > NOW()',
            [$tenantId, $address]
        )->fetch();
        
        if (!$row) {
            return null;
        }
        
        return GeocodeResult::fromArray($row);
    }
    
    public function cacheGeocode(string $tenantId, GeocodeResult $result, int $ttlDays): void
    {
        // Insert into geo_cache table
        $this->db->insert('geo_cache', [
            'id' => $this->generateUlid(),
            'tenant_id' => $tenantId,
            'address' => $result->address,
            'latitude' => $result->coordinates->latitude,
            'longitude' => $result->coordinates->longitude,
            'formatted_address' => $result->formattedAddress,
            'provider' => $result->provider,
            'cached_at' => new \DateTimeImmutable(),
            'expires_at' => (new \DateTimeImmutable())->modify("+{$ttlDays} days"),
        ]);
    }
    
    public function getMetrics(string $tenantId, \DateTimeImmutable $start, \DateTimeImmutable $end): GeoMetrics
    {
        // Calculate cache hit rate and request counts
        // Implementation details...
    }
}
```

#### GeocoderInterface (External API Adapter)

```php
namespace App\Services;

use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Nexus\Geo\ValueObjects\Coordinates;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

final readonly class GoogleMapsGeocoder implements GeocoderInterface
{
    public function __construct(
        private \Geocoder\Provider\GoogleMaps\GoogleMaps $googleProvider,
        private \Geocoder\Provider\Nominatim\Nominatim $nominatimProvider
    ) {}
    
    public function geocode(string $address): GeocodeResult
    {
        try {
            // Try Google Maps first
            $results = $this->googleProvider->geocodeQuery(GeocodeQuery::create($address));
            $location = $results->first();
            
            return new GeocodeResult(
                coordinates: new Coordinates(
                    latitude: $location->getCoordinates()->getLatitude(),
                    longitude: $location->getCoordinates()->getLongitude()
                ),
                formattedAddress: $location->getFormattedAddress(),
                provider: 'google',
                address: $address
            );
        } catch (\Exception $e) {
            // Fallback to Nominatim
            $results = $this->nominatimProvider->geocodeQuery(GeocodeQuery::create($address));
            $location = $results->first();
            
            return new GeocodeResult(
                coordinates: new Coordinates(
                    latitude: $location->getCoordinates()->getLatitude(),
                    longitude: $location->getCoordinates()->getLongitude()
                ),
                formattedAddress: $location->getFormattedAddress(),
                provider: 'nominatim',
                address: $address
            );
        }
    }
    
    public function reverseGeocode(Coordinates $coordinates): GeocodeResult
    {
        // Similar implementation for reverse geocoding
    }
}
```

### Step 2: Bind Interfaces in Service Provider

#### Laravel Example

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\Geo\Contracts\GeofenceInterface;
use App\Repositories\DatabaseGeoRepository;
use App\Services\GoogleMapsGeocoder;
use App\Services\LaravelGeofence;

class GeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            GeoRepositoryInterface::class,
            DatabaseGeoRepository::class
        );
        
        // Bind geocoder
        $this->app->singleton(
            GeocoderInterface::class,
            GoogleMapsGeocoder::class
        );
        
        // Bind geofence
        $this->app->singleton(
            GeofenceInterface::class,
            LaravelGeofence::class
        );
        
        // Register package service provider
        $this->app->register(\Nexus\Geo\ServiceProvider::class);
    }
}
```

#### Symfony Example (services.yaml)

```yaml
services:
    # Repository binding
    Nexus\Geo\Contracts\GeoRepositoryInterface:
        class: App\Repository\DatabaseGeoRepository
        
    # Geocoder binding
    Nexus\Geo\Contracts\GeocoderInterface:
        class: App\Service\GoogleMapsGeocoder
        arguments:
            $googleProvider: '@geocoder.provider.google_maps'
            $nominatimProvider: '@geocoder.provider.nominatim'
```

### Step 3: Use the Package

#### Geocode an Address

```php
use Nexus\Geo\Services\GeocodingManager;
use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\Contracts\GeocoderInterface;

// In your controller or service
public function __construct(
    private readonly GeocodingManager $geocodingManager
) {}

public function geocodeCustomerAddress(string $customerId): void
{
    $customer = $this->customerRepository->find($customerId);
    $address = $customer->getAddress()->formatOneLine();
    
    // Geocode with automatic caching
    $result = $this->geocodingManager->geocode($address);
    
    // Update customer coordinates
    $customer->updateCoordinates($result->coordinates);
    $this->customerRepository->save($customer);
    
    echo "Geocoded: {$result->formattedAddress}\n";
    echo "Lat: {$result->coordinates->latitude}, Lon: {$result->coordinates->longitude}\n";
    echo "Provider: {$result->provider}\n";
}
```

#### Calculate Distance Between Locations

```php
use Nexus\Geo\Services\DistanceCalculator;

$calculator = app(DistanceCalculator::class);

$warehouse = new Coordinates(37.7749, -122.4194); // San Francisco
$customer = new Coordinates(34.0522, -118.2437);  // Los Angeles

$distance = $calculator->calculate($warehouse, $customer);

echo "Distance: {$distance->toKilometers()} km\n";    // ~559 km
echo "Distance: {$distance->toMiles()} miles\n";      // ~347 miles
echo "Distance: {$distance->toMeters()} meters\n";    // ~559,120 m
```

#### Check Geofencing (Point in Polygon)

```php
use Nexus\Geo\Services\GeofenceManager;

$geofence = app(GeofenceManager::class);

// Define delivery zone polygon (array of Coordinates)
$polygon = [
    new Coordinates(37.7749, -122.4194),
    new Coordinates(37.7849, -122.4094),
    new Coordinates(37.7649, -122.4094),
    // ... more vertices
];

$customerLocation = new Coordinates(37.7749, -122.4144);

if ($geofence->contains($customerLocation, $polygon)) {
    echo "Customer is within delivery zone\n";
} else {
    echo "Customer is outside delivery zone\n";
}
```

## Your First Integration

Here's a complete example showing address geocoding with caching:

```php
<?php

use Nexus\Geo\Services\GeocodingManager;
use Nexus\Party\ValueObjects\PostalAddress;

class CustomerAddressService
{
    public function __construct(
        private readonly GeocodingManager $geocodingManager
    ) {}
    
    public function enrichCustomerAddress(string $customerId): void
    {
        // 1. Fetch customer address
        $customer = Customer::find($customerId);
        $address = $customer->postal_address;
        
        // 2. Geocode address (cache-first)
        $result = $this->geocodingManager->geocode(
            $address->formatOneLine()
        );
        
        // 3. Update address with coordinates
        $updatedAddress = $address->withCoordinates($result->coordinates);
        
        // 4. Save back to customer
        $customer->postal_address = $updatedAddress;
        $customer->save();
        
        // Log the result
        logger()->info('Address geocoded', [
            'customer_id' => $customerId,
            'provider' => $result->provider,
            'coordinates' => $result->coordinates->toArray(),
        ]);
    }
}
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples
- Review the root `docs/GEO_IMPLEMENTATION_SUMMARY.md` for comprehensive implementation details

## Troubleshooting

### Common Issues

**Issue 1: "Invalid coordinates" exception**
- **Cause:** Latitude must be between -90 and 90, longitude between -180 and 180
- **Solution:** Validate coordinates before creating `Coordinates` objects

**Issue 2: "Geocoding failed" exception**
- **Cause:** Both Google Maps and Nominatim providers failed
- **Solution:** Check API keys, network connectivity, and address format

**Issue 3: Low cache hit rate (<80%)**
- **Cause:** Addresses formatted inconsistently (e.g., "St" vs "Street")
- **Solution:** Normalize addresses before geocoding using `PostalAddress::formatOneLine()`

**Issue 4: "Polygon complexity exceeded 100 vertices"**
- **Cause:** Region boundary too complex
- **Solution:** Use `PolygonSimplifier` to reduce vertex count with appropriate tolerance
