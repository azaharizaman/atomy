# Integration Guide: Nexus Geo

This guide demonstrates how to integrate `Nexus\Geo` into Laravel and Symfony applications, including complete implementation examples for all required interfaces.

---

## Table of Contents

- [Laravel Integration](#laravel-integration)
- [Symfony Integration](#symfony-integration)
- [Common Patterns](#common-patterns)
- [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Dependencies

```bash
composer require nexus/geo:"*@dev"
composer require geocoder-php/google-maps-provider
composer require geocoder-php/nominatim-provider
composer require php-http/guzzle7-adapter
```

### Step 2: Create Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Geocoding cache table
        Schema::create('geo_cache', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('address', 500)->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('formatted_address', 500);
            $table->string('provider', 50); // 'google' or 'nominatim'
            $table->timestamp('cached_at');
            $table->timestamp('expires_at')->index();
            
            $table->unique(['tenant_id', 'address']);
            $table->index(['tenant_id', 'expires_at']);
        });
        
        // Geofences table
        Schema::create('geofences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('name');
            $table->jsonb('polygon'); // Array of coordinates
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'is_active']);
        });
        
        // Geocoding metrics table (optional)
        Schema::create('geo_metrics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->date('date')->index();
            $table->integer('total_requests')->default(0);
            $table->integer('cache_hits')->default(0);
            $table->integer('cache_misses')->default(0);
            $table->integer('google_api_calls')->default(0);
            $table->integer('nominatim_api_calls')->default(0);
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'date']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('geo_metrics');
        Schema::dropIfExists('geofences');
        Schema::dropIfExists('geo_cache');
    }
};
```

### Step 3: Implement GeoRepositoryInterface

Create `app/Repositories/DatabaseGeoRepository.php`:

```php
<?php

namespace App\Repositories;

use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Nexus\Geo\ValueObjects\GeoMetrics;
use Nexus\Geo\ValueObjects\Coordinates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class DatabaseGeoRepository implements GeoRepositoryInterface
{
    public function findCachedGeocode(string $tenantId, string $address): ?GeocodeResult
    {
        $row = DB::table('geo_cache')
            ->where('tenant_id', $tenantId)
            ->where('address', $address)
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$row) {
            return null;
        }
        
        return new GeocodeResult(
            coordinates: new Coordinates(
                latitude: (float) $row->latitude,
                longitude: (float) $row->longitude
            ),
            formattedAddress: $row->formatted_address,
            provider: $row->provider,
            address: $row->address
        );
    }
    
    public function cacheGeocode(string $tenantId, GeocodeResult $result, int $ttlDays = 90): void
    {
        DB::table('geo_cache')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'address' => $result->address,
            ],
            [
                'id' => Str::ulid()->toString(),
                'latitude' => $result->coordinates->latitude,
                'longitude' => $result->coordinates->longitude,
                'formatted_address' => $result->formattedAddress,
                'provider' => $result->provider,
                'cached_at' => now(),
                'expires_at' => now()->addDays($ttlDays),
            ]
        );
        
        // Track metrics
        $this->incrementMetric($tenantId, 'total_requests');
        $this->incrementMetric($tenantId, 'cache_misses');
        $this->incrementMetric($tenantId, "{$result->provider}_api_calls");
        
        // Update cost ($5 per 1000 requests for Google Maps)
        if ($result->provider === 'google') {
            $this->incrementMetric($tenantId, 'estimated_cost', 0.005);
        }
    }
    
    public function getMetrics(
        string $tenantId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): GeoMetrics {
        $metrics = DB::table('geo_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->selectRaw('
                SUM(total_requests) as total_requests,
                SUM(cache_hits) as cache_hits,
                SUM(cache_misses) as cache_misses,
                SUM(google_api_calls + nominatim_api_calls) as api_calls,
                SUM(estimated_cost) as estimated_cost
            ')
            ->first();
        
        $totalRequests = $metrics->total_requests ?? 0;
        $cacheHits = $metrics->cache_hits ?? 0;
        $cacheMisses = $metrics->cache_misses ?? 0;
        
        return new GeoMetrics(
            totalRequests: $totalRequests,
            cacheHits: $cacheHits,
            cacheMisses: $cacheMisses,
            apiCalls: $metrics->api_calls ?? 0,
            cacheHitRate: $totalRequests > 0 ? ($cacheHits / $totalRequests) * 100 : 0,
            estimatedCost: (float) ($metrics->estimated_cost ?? 0)
        );
    }
    
    private function incrementMetric(string $tenantId, string $field, float $amount = 1): void
    {
        DB::table('geo_metrics')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'date' => now()->format('Y-m-d'),
            ],
            [
                'id' => Str::ulid()->toString(),
                $field => DB::raw("{$field} + {$amount}"),
                'updated_at' => now(),
            ]
        );
    }
}
```

### Step 4: Implement GeocoderInterface

Create `app/Services/GoogleMapsGeocoder.php`:

```php
<?php

namespace App\Services;

use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\Exceptions\GeocodingFailedException;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Provider\Nominatim\Nominatim;
use Psr\Log\LoggerInterface;

final readonly class GoogleMapsGeocoder implements GeocoderInterface
{
    public function __construct(
        private GoogleMaps $googleProvider,
        private Nominatim $nominatimProvider,
        private LoggerInterface $logger
    ) {}
    
    public function geocode(string $address): GeocodeResult
    {
        // Try Google Maps first
        try {
            $this->logger->debug('Attempting geocoding with Google Maps', ['address' => $address]);
            
            $collection = $this->googleProvider->geocodeQuery(
                GeocodeQuery::create($address)
            );
            
            if ($collection->isEmpty()) {
                throw new \Exception('No results from Google Maps');
            }
            
            $location = $collection->first();
            
            return new GeocodeResult(
                coordinates: new Coordinates(
                    latitude: $location->getCoordinates()->getLatitude(),
                    longitude: $location->getCoordinates()->getLongitude()
                ),
                formattedAddress: $location->getFormattedAddress() ?? $address,
                provider: 'google',
                address: $address
            );
            
        } catch (\Throwable $googleError) {
            $this->logger->warning('Google Maps geocoding failed', [
                'address' => $address,
                'error' => $googleError->getMessage()
            ]);
            
            // Fallback to Nominatim
            try {
                $this->logger->debug('Falling back to Nominatim', ['address' => $address]);
                
                $collection = $this->nominatimProvider->geocodeQuery(
                    GeocodeQuery::create($address)
                );
                
                if ($collection->isEmpty()) {
                    throw new \Exception('No results from Nominatim');
                }
                
                $location = $collection->first();
                
                return new GeocodeResult(
                    coordinates: new Coordinates(
                        latitude: $location->getCoordinates()->getLatitude(),
                        longitude: $location->getCoordinates()->getLongitude()
                    ),
                    formattedAddress: $location->getFormattedAddress() ?? $address,
                    provider: 'nominatim',
                    address: $address
                );
                
            } catch (\Throwable $nominatimError) {
                $this->logger->error('All geocoding providers failed', [
                    'address' => $address,
                    'google_error' => $googleError->getMessage(),
                    'nominatim_error' => $nominatimError->getMessage()
                ]);
                
                throw GeocodingFailedException::allProvidersFailed($address);
            }
        }
    }
    
    public function reverseGeocode(Coordinates $coordinates): GeocodeResult
    {
        try {
            $collection = $this->googleProvider->reverseQuery(
                ReverseQuery::fromCoordinates(
                    $coordinates->latitude,
                    $coordinates->longitude
                )
            );
            
            if ($collection->isEmpty()) {
                throw new \Exception('No results from Google Maps');
            }
            
            $location = $collection->first();
            
            return new GeocodeResult(
                coordinates: $coordinates,
                formattedAddress: $location->getFormattedAddress() ?? '',
                provider: 'google',
                address: $location->getFormattedAddress() ?? ''
            );
            
        } catch (\Throwable $e) {
            // Fallback to Nominatim
            $collection = $this->nominatimProvider->reverseQuery(
                ReverseQuery::fromCoordinates(
                    $coordinates->latitude,
                    $coordinates->longitude
                )
            );
            
            if ($collection->isEmpty()) {
                throw GeocodingFailedException::forAddress(
                    "({$coordinates->latitude}, {$coordinates->longitude})",
                    'No results from any provider'
                );
            }
            
            $location = $collection->first();
            
            return new GeocodeResult(
                coordinates: $coordinates,
                formattedAddress: $location->getFormattedAddress() ?? '',
                provider: 'nominatim',
                address: $location->getFormattedAddress() ?? ''
            );
        }
    }
}
```

### Step 5: Implement GeofenceInterface

Create `app/Repositories/DatabaseGeofenceRepository.php`:

```php
<?php

namespace App\Repositories;

use Nexus\Geo\Contracts\GeofenceInterface;
use Nexus\Geo\ValueObjects\Polygon;
use Nexus\Geo\ValueObjects\Coordinates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class DatabaseGeofenceRepository implements GeofenceInterface
{
    public function getAllGeofences(string $tenantId): array
    {
        $rows = DB::table('geofences')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        
        return $rows->map(function ($row) {
            return new Polygon(
                array_map(
                    fn($vertex) => new Coordinates($vertex['latitude'], $vertex['longitude']),
                    json_decode($row->polygon, true)
                )
            );
        })->toArray();
    }
    
    public function findGeofenceById(string $geofenceId): ?Polygon
    {
        $row = DB::table('geofences')
            ->where('id', $geofenceId)
            ->where('is_active', true)
            ->first();
        
        if (!$row) {
            return null;
        }
        
        return new Polygon(
            array_map(
                fn($vertex) => new Coordinates($vertex['latitude'], $vertex['longitude']),
                json_decode($row->polygon, true)
            )
        );
    }
    
    public function saveGeofence(string $tenantId, string $geofenceId, Polygon $polygon): void
    {
        DB::table('geofences')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'id' => $geofenceId,
            ],
            [
                'polygon' => json_encode($polygon->toArray()),
                'updated_at' => now(),
            ]
        );
    }
}
```

### Step 6: Create Service Provider

Create `app/Providers/GeoServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\Geo\Contracts\GeofenceInterface;
use App\Repositories\DatabaseGeoRepository;
use App\Repositories\DatabaseGeofenceRepository;
use App\Services\GoogleMapsGeocoder;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Provider\Nominatim\Nominatim;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;

class GeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repository implementations
        $this->app->singleton(GeoRepositoryInterface::class, DatabaseGeoRepository::class);
        $this->app->singleton(GeofenceInterface::class, DatabaseGeofenceRepository::class);
        
        // Register geocoding providers
        $this->app->singleton(GoogleMaps::class, function () {
            $httpClient = new GuzzleAdapter();
            $apiKey = config('services.google_maps.api_key');
            
            return new GoogleMaps($httpClient, null, $apiKey);
        });
        
        $this->app->singleton(Nominatim::class, function () {
            $httpClient = new GuzzleAdapter();
            $userAgent = config('app.name') . ' Geocoder';
            
            return Nominatim::withOpenStreetMapServer($httpClient, $userAgent);
        });
        
        // Register geocoder implementation
        $this->app->singleton(GeocoderInterface::class, GoogleMapsGeocoder::class);
    }
}
```

### Step 7: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\GeoServiceProvider::class,
],
```

### Step 8: Add Configuration

Add to `config/services.php`:

```php
return [
    // ...
    
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],
];
```

Add to `.env`:

```env
GOOGLE_MAPS_API_KEY=your_api_key_here
```

### Step 9: Use in Controllers/Services

```php
<?php

namespace App\Http\Controllers;

use Nexus\Geo\Services\GeocodingManager;
use Nexus\Geo\Services\DistanceCalculator;
use Nexus\Geo\Services\GeofenceManager;
use Nexus\Geo\ValueObjects\Coordinates;

class DeliveryController extends Controller
{
    public function __construct(
        private readonly GeocodingManager $geocoding,
        private readonly DistanceCalculator $distance,
        private readonly GeofenceManager $geofence
    ) {}
    
    public function calculateDeliveryFee(Request $request)
    {
        // Geocode customer address
        $result = $this->geocoding->geocode($request->input('address'));
        
        // Calculate distance from warehouse
        $warehouse = new Coordinates(37.7749, -122.4194);
        $distance = $this->distance->calculate($warehouse, $result->coordinates);
        
        // Check delivery zone
        $deliveryZone = $this->getDeliveryZone(); // Array of Coordinates
        $inZone = $this->geofence->contains($result->coordinates, $deliveryZone);
        
        // Calculate fee
        $fee = $inZone ? 5.00 : 5.00 + ($distance->toKilometers() * 0.50);
        
        return response()->json([
            'address' => $result->formattedAddress,
            'distance_km' => $distance->toKilometers(),
            'in_delivery_zone' => $inZone,
            'delivery_fee' => $fee,
            'provider' => $result->provider,
        ]);
    }
}
```

---

## Symfony Integration

### Step 1: Install Dependencies

```bash
composer require nexus/geo:"*@dev"
composer require geocoder-php/google-maps-provider
composer require geocoder-php/nominatim-provider
composer require php-http/guzzle7-adapter
```

### Step 2: Create Doctrine Entities

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'geo_cache')]
#[ORM\Index(columns: ['tenant_id', 'address'])]
#[ORM\Index(columns: ['expires_at'])]
class GeoCache
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;
    
    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;
    
    #[ORM\Column(type: 'string', length: 500)]
    private string $address;
    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private float $latitude;
    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private float $longitude;
    
    #[ORM\Column(type: 'string', length: 500)]
    private string $formattedAddress;
    
    #[ORM\Column(type: 'string', length: 50)]
    private string $provider;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $cachedAt;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;
    
    // Getters and setters...
}
```

### Step 3: Implement Repositories

Create `src/Repository/DoctrineGeoRepository.php`:

```php
<?php

namespace App\Repository;

use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Nexus\Geo\ValueObjects\GeoMetrics;
use Nexus\Geo\ValueObjects\Coordinates;
use App\Entity\GeoCache;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineGeoRepository implements GeoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}
    
    public function findCachedGeocode(string $tenantId, string $address): ?GeocodeResult
    {
        $cache = $this->entityManager->getRepository(GeoCache::class)
            ->createQueryBuilder('gc')
            ->where('gc.tenantId = :tenantId')
            ->andWhere('gc.address = :address')
            ->andWhere('gc.expiresAt > :now')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('address', $address)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$cache) {
            return null;
        }
        
        return new GeocodeResult(
            coordinates: new Coordinates($cache->getLatitude(), $cache->getLongitude()),
            formattedAddress: $cache->getFormattedAddress(),
            provider: $cache->getProvider(),
            address: $cache->getAddress()
        );
    }
    
    public function cacheGeocode(string $tenantId, GeocodeResult $result, int $ttlDays = 90): void
    {
        $cache = new GeoCache();
        $cache->setId($this->generateUlid());
        $cache->setTenantId($tenantId);
        $cache->setAddress($result->address);
        $cache->setLatitude($result->coordinates->latitude);
        $cache->setLongitude($result->coordinates->longitude);
        $cache->setFormattedAddress($result->formattedAddress);
        $cache->setProvider($result->provider);
        $cache->setCachedAt(new \DateTimeImmutable());
        $cache->setExpiresAt((new \DateTimeImmutable())->modify("+{$ttlDays} days"));
        
        $this->entityManager->persist($cache);
        $this->entityManager->flush();
    }
    
    public function getMetrics(
        string $tenantId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): GeoMetrics {
        // Implementation similar to Laravel
        // Query GeoMetrics entity and calculate
    }
}
```

### Step 4: Configure Services

Create `config/services.yaml`:

```yaml
services:
    # Repository implementations
    Nexus\Geo\Contracts\GeoRepositoryInterface:
        class: App\Repository\DoctrineGeoRepository
        
    Nexus\Geo\Contracts\GeofenceInterface:
        class: App\Repository\DoctrineGeofenceRepository
    
    # Geocoding providers
    Geocoder\Provider\GoogleMaps\GoogleMaps:
        arguments:
            $httpClient: '@Http\Adapter\Guzzle7\Client'
            $apiKey: '%env(GOOGLE_MAPS_API_KEY)%'
    
    Geocoder\Provider\Nominatim\Nominatim:
        factory: ['Geocoder\Provider\Nominatim\Nominatim', 'withOpenStreetMapServer']
        arguments:
            $httpClient: '@Http\Adapter\Guzzle7\Client'
            $userAgent: 'MyApp Geocoder'
    
    # Geocoder implementation
    Nexus\Geo\Contracts\GeocoderInterface:
        class: App\Service\SymfonyGeocoder
        
    # HTTP client
    Http\Adapter\Guzzle7\Client:
        class: Http\Adapter\Guzzle7\Client
```

### Step 5: Use in Controllers

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\Geo\Services\GeocodingManager;
use Nexus\Geo\Services\DistanceCalculator;

class DeliveryController extends AbstractController
{
    public function __construct(
        private readonly GeocodingManager $geocoding,
        private readonly DistanceCalculator $distance
    ) {}
    
    #[Route('/api/delivery-fee', name: 'calculate_delivery_fee')]
    public function calculateDeliveryFee(Request $request): JsonResponse
    {
        $address = $request->query->get('address');
        
        $result = $this->geocoding->geocode($address);
        
        // Calculate distance and fee...
        
        return new JsonResponse([
            'address' => $result->formattedAddress,
            'coordinates' => $result->coordinates->toArray(),
        ]);
    }
}
```

---

## Common Patterns

### Pattern 1: Batch Geocoding

```php
public function batchGeocodeCustomers(): void
{
    $customers = Customer::whereNull('latitude')->limit(100)->get();
    
    foreach ($customers as $customer) {
        try {
            $result = $this->geocoding->geocode($customer->address);
            
            $customer->update([
                'latitude' => $result->coordinates->latitude,
                'longitude' => $result->coordinates->longitude,
                'formatted_address' => $result->formattedAddress,
            ]);
            
            // Rate limiting: sleep 100ms between requests
            usleep(100000);
            
        } catch (GeocodingFailedException $e) {
            logger()->warning('Geocoding failed', [
                'customer_id' => $customer->id,
                'address' => $customer->address,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### Pattern 2: Delivery Zone Validation

```php
public function validateDeliveryAddress(string $address): array
{
    // Geocode address
    $result = $this->geocoding->geocode($address);
    
    // Define delivery zones
    $zones = [
        'zone_a' => [/* array of Coordinates */],
        'zone_b' => [/* array of Coordinates */],
    ];
    
    // Check each zone
    foreach ($zones as $zoneName => $polygon) {
        if ($this->geofence->contains($result->coordinates, $polygon)) {
            return [
                'in_delivery_zone' => true,
                'zone' => $zoneName,
                'coordinates' => $result->coordinates->toArray(),
            ];
        }
    }
    
    return [
        'in_delivery_zone' => false,
        'zone' => null,
        'coordinates' => $result->coordinates->toArray(),
    ];
}
```

### Pattern 3: Nearest Location Finder

```php
public function findNearestWarehouse(Coordinates $customerLocation): array
{
    $warehouses = [
        'SF Warehouse' => new Coordinates(37.7749, -122.4194),
        'LA Warehouse' => new Coordinates(34.0522, -118.2437),
        'NY Warehouse' => new Coordinates(40.7128, -74.0060),
    ];
    
    $nearest = null;
    $shortestDistance = null;
    
    foreach ($warehouses as $name => $coords) {
        $distance = $this->distance->calculate($customerLocation, $coords);
        
        if ($shortestDistance === null || $distance->toMeters() < $shortestDistance->toMeters()) {
            $nearest = $name;
            $shortestDistance = $distance;
        }
    }
    
    return [
        'warehouse' => $nearest,
        'distance_km' => $shortestDistance->toKilometers(),
    ];
}
```

---

## Troubleshooting

### Issue 1: Google Maps API Quota Exceeded

**Solution:** Implement request throttling and prioritize cache hits.

```php
// In your repository implementation
public function cacheGeocode(string $tenantId, GeocodeResult $result, int $ttlDays = 90): void
{
    // Extend cache TTL to 180 days during high-traffic periods
    if ($this->isHighTrafficPeriod()) {
        $ttlDays = 180;
    }
    
    // ... rest of implementation
}
```

### Issue 2: Nominatim Rate Limiting

**Solution:** Add delay between requests to respect Nominatim's 1 req/second limit.

```php
private static ?\DateTimeImmutable $lastNominatimRequest = null;

public function geocode(string $address): GeocodeResult
{
    // ... Google Maps attempt
    
    // Before calling Nominatim
    if (self::$lastNominatimRequest !== null) {
        $elapsed = time() - self::$lastNominatimRequest->getTimestamp();
        if ($elapsed < 1) {
            usleep((1 - $elapsed) * 1000000);
        }
    }
    
    self::$lastNominatimRequest = new \DateTimeImmutable();
    
    // ... Nominatim call
}
```

### Issue 3: Polygon Too Complex

**Solution:** Use `PolygonSimplifier` before storing.

```php
use Nexus\Geo\Services\PolygonSimplifier;

$simplifier = new PolygonSimplifier();

try {
    $polygon = new Polygon($vertices); // May throw if > 100 vertices
} catch (GeofenceValidationException $e) {
    // Simplify with increasing tolerance until it fits
    $tolerance = 0.0001;
    while (count($vertices) > 100 && $tolerance < 0.01) {
        $vertices = $simplifier->simplify($vertices, $tolerance);
        $tolerance *= 2;
    }
    
    $polygon = new Polygon($vertices);
}
```

---

**For more information:**
- [Getting Started Guide](getting-started.md)
- [API Reference](api-reference.md)
- [Code Examples](examples/)
