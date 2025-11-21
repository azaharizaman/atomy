<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Geo\Services\GeocodingManager;
use Nexus\Geo\Services\DistanceCalculator;
use Nexus\Geo\Services\GeofenceManager;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\Exceptions\GeocodingFailedException;
use Nexus\Geo\Exceptions\InvalidCoordinatesException;
use Nexus\Geo\Contracts\GeoRepositoryInterface;

final class GeoController extends Controller
{
    public function __construct(
        private readonly GeocodingManager $geocodingManager,
        private readonly DistanceCalculator $distanceCalculator,
        private readonly GeofenceManager $geofenceManager,
        private readonly GeoRepositoryInterface $geoRepository
    ) {}

    /**
     * POST /api/geo/geocode
     * 
     * Convert address to coordinates.
     */
    public function geocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string|max:500',
        ]);

        try {
            $tenantId = $this->getTenantId($request->user());
            
            $result = $this->geocodingManager->geocode(
                $validated['address'],
                $tenantId
            );

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);
        } catch (GeocodingFailedException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/geo/reverse-geocode
     * 
     * Convert coordinates to address.
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $tenantId = $this->getTenantId($request->user());
            
            $coordinates = new Coordinates(
                latitude: $validated['latitude'],
                longitude: $validated['longitude']
            );

            $address = $this->geocodingManager->reverseGeocode($coordinates, $tenantId);

            return response()->json([
                'success' => true,
                'data' => [
                    'address' => $address,
                    'coordinates' => $coordinates->toArray(),
                ],
            ]);
        } catch (InvalidCoordinatesException | GeocodingFailedException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/geo/distance
     * 
     * Calculate distance between two points.
     */
    public function calculateDistance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|array',
            'from.latitude' => 'required|numeric|between:-90,90',
            'from.longitude' => 'required|numeric|between:-180,180',
            'to' => 'required|array',
            'to.latitude' => 'required|numeric|between:-90,90',
            'to.longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $from = new Coordinates(
                latitude: $validated['from']['latitude'],
                longitude: $validated['from']['longitude']
            );

            $to = new Coordinates(
                latitude: $validated['to']['latitude'],
                longitude: $validated['to']['longitude']
            );

            $distance = $this->distanceCalculator->calculate($from, $to);

            return response()->json([
                'success' => true,
                'data' => [
                    'distance' => $distance->toArray(),
                    'formatted' => [
                        'km' => $distance->format('km'),
                        'mi' => $distance->format('mi'),
                    ],
                ],
            ]);
        } catch (InvalidCoordinatesException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/geo/geofence/check
     * 
     * Check if a point is inside a geofence region.
     */
    public function checkGeofence(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'point' => 'required|array',
            'point.latitude' => 'required|numeric|between:-90,90',
            'point.longitude' => 'required|numeric|between:-180,180',
            'region_code' => 'required|string|max:50',
        ]);

        try {
            $tenantId = $this->getTenantId($request->user());
            
            $point = new Coordinates(
                latitude: $validated['point']['latitude'],
                longitude: $validated['point']['longitude']
            );

            $isInside = $this->geofenceManager->isPointInRegion(
                $point,
                $validated['region_code'],
                $tenantId
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'is_inside' => $isInside,
                    'region_code' => $validated['region_code'],
                    'point' => $point->toArray(),
                ],
            ]);
        } catch (InvalidCoordinatesException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/geo/regions
     * 
     * List all geofence regions for tenant.
     */
    public function listRegions(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request->user());
        
        $regions = $this->geoRepository->listRegions($tenantId);

        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    /**
     * GET /api/geo/cache-metrics
     * 
     * Get geocoding cache performance metrics.
     */
    public function getCacheMetrics(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request->user());
        
        $metrics = $this->geoRepository->getCacheMetrics($tenantId);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    private function getTenantId(?User $user): string
    {
        // TODO: Implement proper tenant resolution
        return $user?->tenant_id ?? 'default-tenant';
    }
}
