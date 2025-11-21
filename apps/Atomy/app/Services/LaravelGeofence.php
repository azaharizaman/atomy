<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Geo\Contracts\GeofenceInterface;
use Nexus\Geo\ValueObjects\Coordinates;

final readonly class LaravelGeofence implements GeofenceInterface
{
    /**
     * Check if a point is inside a polygon using ray-casting algorithm.
     * 
     * @param Coordinates $point The point to test
     * @param array<int, array{lat: float, lng: float}> $polygon Array of lat/lng points
     * @return bool True if point is inside polygon
     */
    public function isPointInPolygon(Coordinates $point, array $polygon): bool
    {
        $vertices = count($polygon);
        $x = $point->longitude;
        $y = $point->latitude;
        $inside = false;

        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $polygon[$i]['lng'];
            $yi = $polygon[$i]['lat'];
            $xj = $polygon[$j]['lng'];
            $yj = $polygon[$j]['lat'];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Check if a point is within a circular geofence.
     * 
     * @param Coordinates $point The point to test
     * @param Coordinates $center The center of the circle
     * @param float $radiusMeters The radius in meters
     * @return bool True if point is within radius
     */
    public function isPointInCircle(
        Coordinates $point,
        Coordinates $center,
        float $radiusMeters
    ): bool {
        $distance = $this->calculateHaversineDistance($point, $center);
        return $distance <= $radiusMeters;
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private function calculateHaversineDistance(Coordinates $from, Coordinates $to): float
    {
        $earthRadius = 6371000; // meters
        $latFrom = deg2rad($from->latitude);
        $lonFrom = deg2rad($from->longitude);
        $latTo = deg2rad($to->latitude);
        $lonTo = deg2rad($to->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate the area of a polygon in square meters.
     * Uses spherical excess formula for accuracy on Earth's surface.
     * 
     * @param array<int, array{lat: float, lng: float}> $polygon
     * @return float Area in square meters
     */
    public function calculatePolygonArea(array $polygon): float
    {
        if (count($polygon) < 3) {
            return 0.0;
        }

        $earthRadius = 6371000; // meters
        $area = 0.0;

        for ($i = 0; $i < count($polygon); $i++) {
            $p1 = $polygon[$i];
            $p2 = $polygon[($i + 1) % count($polygon)];

            $lat1 = deg2rad($p1['lat']);
            $lat2 = deg2rad($p2['lat']);
            $lon1 = deg2rad($p1['lng']);
            $lon2 = deg2rad($p2['lng']);

            $area += ($lon2 - $lon1) * (2 + sin($lat1) + sin($lat2));
        }

        $area = abs($area * $earthRadius * $earthRadius / 2);

        return $area;
    }
}
