<?php

declare(strict_types=1);

namespace Nexus\Geo\Services;

use Nexus\Geo\Contracts\PolygonSimplifierInterface;
use Nexus\Geo\ValueObjects\PolygonSimplificationResult;
use Nexus\Geo\Exceptions\PolygonComplexityException;
use Psr\Log\LoggerInterface;

/**
 * Stateless polygon simplification service
 * 
 * Uses Douglas-Peucker algorithm to reduce polygon complexity
 */
final readonly class PolygonSimplifier implements PolygonSimplifierInterface
{
    private const DEFAULT_TOLERANCE = 10.0; // 10 meters
    private const DEFAULT_MAX_VERTICES = 100;

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function simplify(
        array $polygon,
        float $tolerance = self::DEFAULT_TOLERANCE,
        int $maxVertices = self::DEFAULT_MAX_VERTICES
    ): PolygonSimplificationResult {
        $originalCount = count($polygon);

        // Validate input
        if ($originalCount < 3) {
            throw PolygonComplexityException::invalidPolygon(
                'Polygon must have at least 3 vertices'
            );
        }

        // Already simple enough
        if ($this->isSimpleEnough($polygon, $maxVertices)) {
            $this->logger->debug('Polygon already simple', ['vertices' => $originalCount]);
            
            return new PolygonSimplificationResult(
                $polygon,
                $polygon,
                $originalCount,
                $originalCount,
                $tolerance
            );
        }

        // Apply Douglas-Peucker algorithm
        $simplified = $this->douglasPeucker($polygon, $tolerance);
        $simplifiedCount = count($simplified);

        // Check if still too complex
        if ($simplifiedCount > $maxVertices) {
            // Try with increased tolerance
            $recommendedTolerance = $this->calculateRecommendedTolerance($polygon, $maxVertices);
            $simplified = $this->douglasPeucker($polygon, $recommendedTolerance);
            $simplifiedCount = count($simplified);
            $tolerance = $recommendedTolerance;

            if ($simplifiedCount > $maxVertices) {
                throw PolygonComplexityException::tooManyVertices($simplifiedCount, $maxVertices);
            }
        }

        $this->logger->info('Polygon simplified', [
            'original_vertices' => $originalCount,
            'simplified_vertices' => $simplifiedCount,
            'tolerance' => $tolerance,
        ]);

        return new PolygonSimplificationResult(
            $polygon,
            $simplified,
            $originalCount,
            $simplifiedCount,
            $tolerance
        );
    }

    public function validateComplexity(array $polygon, int $maxVertices = self::DEFAULT_MAX_VERTICES): void
    {
        $count = count($polygon);
        
        if ($count > $maxVertices) {
            throw PolygonComplexityException::tooManyVertices($count, $maxVertices);
        }

        if ($count < 3) {
            throw PolygonComplexityException::invalidPolygon(
                'Polygon must have at least 3 vertices'
            );
        }
    }

    public function calculateRecommendedTolerance(array $polygon, int $targetVertices): float
    {
        $currentCount = count($polygon);
        
        if ($currentCount <= $targetVertices) {
            return self::DEFAULT_TOLERANCE;
        }

        // Estimate based on reduction ratio
        $reductionRatio = $targetVertices / $currentCount;
        
        // Higher reduction needs higher tolerance (exponential relationship)
        $baseTolerance = self::DEFAULT_TOLERANCE;
        $tolerance = $baseTolerance * pow(1 / $reductionRatio, 1.5);

        return round($tolerance, 2);
    }

    public function isSimpleEnough(array $polygon, int $maxVertices = self::DEFAULT_MAX_VERTICES): bool
    {
        return count($polygon) <= $maxVertices;
    }

    /**
     * Douglas-Peucker algorithm implementation
     * 
     * @param array $polygon Array of [lat, lng] pairs
     * @param float $tolerance Tolerance in meters
     * @return array Simplified polygon
     */
    private function douglasPeucker(array $polygon, float $tolerance): array
    {
        if (count($polygon) <= 2) {
            return $polygon;
        }

        // Find point with maximum distance from line
        $maxDistance = 0;
        $maxIndex = 0;
        $end = count($polygon) - 1;

        for ($i = 1; $i < $end; $i++) {
            $distance = $this->perpendicularDistance(
                $polygon[$i],
                $polygon[0],
                $polygon[$end]
            );

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $maxIndex = $i;
            }
        }

        // If max distance is greater than tolerance, recursively simplify
        if ($maxDistance > $tolerance) {
            // Recursive call
            $left = $this->douglasPeucker(
                array_slice($polygon, 0, $maxIndex + 1),
                $tolerance
            );
            $right = $this->douglasPeucker(
                array_slice($polygon, $maxIndex),
                $tolerance
            );

            // Combine results (remove duplicate middle point)
            return array_merge(
                array_slice($left, 0, -1),
                $right
            );
        }

        // Base case - return only endpoints
        return [$polygon[0], $polygon[$end]];
    }

    /**
     * Calculate perpendicular distance from point to line
     * 
     * @param array $point [lat, lng]
     * @param array $lineStart [lat, lng]
     * @param array $lineEnd [lat, lng]
     * @return float Distance in meters
     */
    private function perpendicularDistance(array $point, array $lineStart, array $lineEnd): float
    {
        // Convert to radians
        $lat1 = deg2rad($lineStart[0]);
        $lon1 = deg2rad($lineStart[1]);
        $lat2 = deg2rad($lineEnd[0]);
        $lon2 = deg2rad($lineEnd[1]);
        $lat3 = deg2rad($point[0]);
        $lon3 = deg2rad($point[1]);

        // Haversine-based perpendicular distance
        $R = 6371000; // Earth's radius in meters

        $y = sin($lon3 - $lon1) * cos($lat3);
        $x = cos($lat1) * sin($lat3) - sin($lat1) * cos($lat3) * cos($lon3 - $lon1);
        $bearing1 = atan2($y, $x);

        $y = sin($lon2 - $lon1) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lon2 - $lon1);
        $bearing2 = atan2($y, $x);

        $bearingDiff = $bearing2 - $bearing1;

        $dLat = $lat3 - $lat1;
        $dLon = $lon3 - $lon1;
        $a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat3) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $R * $c;

        return abs($distance * sin($bearingDiff));
    }
}
