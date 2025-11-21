<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Routing\Services\TspOptimizer;
use Nexus\Routing\Services\VrpOptimizer;
use Nexus\Routing\ValueObjects\RouteStop;
use Nexus\Routing\ValueObjects\VehicleProfile;
use Nexus\Routing\ValueObjects\RouteConstraints;
use Nexus\Routing\Contracts\RouteCacheInterface;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Routing\Exceptions\RouteOptimizationException;
use DateTimeImmutable;

final class RoutingController extends Controller
{
    public function __construct(
        private readonly TspOptimizer $tspOptimizer,
        private readonly VrpOptimizer $vrpOptimizer,
        private readonly RouteCacheInterface $routeCache
    ) {}

    /**
     * POST /api/routing/tsp
     * 
     * Optimize route for single vehicle (Traveling Salesman Problem).
     */
    public function optimizeTsp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stops' => 'required|array|min:2',
            'stops.*.id' => 'required|string',
            'stops.*.latitude' => 'required|numeric|between:-90,90',
            'stops.*.longitude' => 'required|numeric|between:-180,180',
            'stops.*.service_duration_seconds' => 'sometimes|integer|min:0',
            'depot_index' => 'sometimes|integer|min:0',
        ]);

        try {
            $tenantId = $this->getTenantId($request->user());
            
            // Build RouteStop array
            $stops = array_map(function ($stop) {
                return new RouteStop(
                    id: $stop['id'],
                    coordinates: new Coordinates(
                        latitude: $stop['latitude'],
                        longitude: $stop['longitude']
                    ),
                    serviceDurationSeconds: $stop['service_duration_seconds'] ?? 0
                );
            }, $validated['stops']);

            $depotIndex = $validated['depot_index'] ?? 0;

            // Optimize route
            $result = $this->tspOptimizer->optimize($stops, $depotIndex);

            return response()->json([
                'success' => true,
                'data' => [
                    'optimized_route' => $result->optimizedRoute->toArray(),
                    'metrics' => $result->metrics->toArray(),
                ],
            ]);
        } catch (RouteOptimizationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/routing/vrp
     * 
     * Optimize routes for multiple vehicles (Vehicle Routing Problem).
     */
    public function optimizeVrp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stops' => 'required|array|min:2',
            'stops.*.id' => 'required|string',
            'stops.*.latitude' => 'required|numeric|between:-90,90',
            'stops.*.longitude' => 'required|numeric|between:-180,180',
            'stops.*.demand' => 'sometimes|numeric|min:0',
            'stops.*.service_duration_seconds' => 'sometimes|integer|min:0',
            'vehicles' => 'required|array|min:1',
            'vehicles.*.id' => 'required|string',
            'vehicles.*.capacity' => 'required|numeric|min:0',
            'vehicles.*.start_latitude' => 'required|numeric|between:-90,90',
            'vehicles.*.start_longitude' => 'required|numeric|between:-180,180',
            'constraints' => 'sometimes|array',
            'constraints.max_route_duration_seconds' => 'sometimes|integer|min:0',
        ]);

        try {
            $tenantId = $this->getTenantId($request->user());
            
            // Build RouteStop array
            $stops = array_map(function ($stop) {
                return new RouteStop(
                    id: $stop['id'],
                    coordinates: new Coordinates(
                        latitude: $stop['latitude'],
                        longitude: $stop['longitude']
                    ),
                    serviceDurationSeconds: $stop['service_duration_seconds'] ?? 0,
                    demand: $stop['demand'] ?? 0.0
                );
            }, $validated['stops']);

            // Build VehicleProfile array
            $vehicles = array_map(function ($vehicle) {
                return new VehicleProfile(
                    vehicleId: $vehicle['id'],
                    capacity: $vehicle['capacity'],
                    startLocation: new Coordinates(
                        latitude: $vehicle['start_latitude'],
                        longitude: $vehicle['start_longitude']
                    )
                );
            }, $validated['vehicles']);

            // Build constraints
            $constraints = null;
            if (isset($validated['constraints'])) {
                $constraints = new RouteConstraints(
                    maxRouteDurationSeconds: $validated['constraints']['max_route_duration_seconds'] ?? null
                );
            }

            // Optimize routes
            $result = $this->vrpOptimizer->optimize($stops, $vehicles, $constraints);

            return response()->json([
                'success' => true,
                'data' => [
                    'optimized_routes' => array_map(
                        fn($route) => $route->toArray(),
                        $result->optimizedRoutes
                    ),
                    'metrics' => $result->metrics->toArray(),
                    'violations' => array_map(
                        fn($v) => $v->toArray(),
                        $result->constraintViolations
                    ),
                ],
            ]);
        } catch (RouteOptimizationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/routing/cache-metrics
     * 
     * Get route cache performance metrics.
     */
    public function getCacheMetrics(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request->user());
        
        $metrics = $this->routeCache->getCacheMetrics($tenantId);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * DELETE /api/routing/cache
     * 
     * Clear route cache for tenant.
     */
    public function clearCache(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request->user());
        
        $deletedCount = $this->routeCache->clearAllCache($tenantId);

        return response()->json([
            'success' => true,
            'data' => [
                'deleted_entries' => $deletedCount,
            ],
        ]);
    }

    private function getTenantId(?User $user): string
    {
        // TODO: Implement proper tenant resolution
        return $user?->tenant_id ?? 'default-tenant';
    }
}
