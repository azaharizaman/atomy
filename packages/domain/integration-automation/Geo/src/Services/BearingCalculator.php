<?php

declare(strict_types=1);

namespace Nexus\Geo\Services;

use Nexus\Geo\Contracts\BearingCalculatorInterface;
use Nexus\Geo\ValueObjects\Coordinates;

/**
 * Stateless bearing calculator for compass directions
 * 
 * Calculates bearings and compass directions between coordinates
 */
final readonly class BearingCalculator implements BearingCalculatorInterface
{
    private const EARTH_RADIUS_METERS = 6371000;

    private const COMPASS_DIRECTIONS = [
        'N' => [337.5, 22.5],
        'NE' => [22.5, 67.5],
        'E' => [67.5, 112.5],
        'SE' => [112.5, 157.5],
        'S' => [157.5, 202.5],
        'SW' => [202.5, 247.5],
        'W' => [247.5, 292.5],
        'NW' => [292.5, 337.5],
    ];

    public function calculateInitialBearing(Coordinates $from, Coordinates $to): float
    {
        $lat1 = deg2rad($from->latitude);
        $lon1 = deg2rad($from->longitude);
        $lat2 = deg2rad($to->latitude);
        $lon2 = deg2rad($to->longitude);

        $dLon = $lon2 - $lon1;

        $y = sin($dLon) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dLon);

        $bearing = atan2($y, $x);
        $bearingDegrees = rad2deg($bearing);

        // Normalize to 0-360
        return fmod($bearingDegrees + 360, 360);
    }

    public function calculateFinalBearing(Coordinates $from, Coordinates $to): float
    {
        // Final bearing is reverse bearing + 180
        $reverseBearing = $this->calculateInitialBearing($to, $from);
        return fmod($reverseBearing + 180, 360);
    }

    public function getCompassDirection(float $bearing): string
    {
        // Normalize bearing to 0-360
        $normalizedBearing = fmod($bearing + 360, 360);

        foreach (self::COMPASS_DIRECTIONS as $direction => [$min, $max]) {
            if ($direction === 'N') {
                // Special case for North (wraps around 0)
                if ($normalizedBearing >= $min || $normalizedBearing < $max) {
                    return $direction;
                }
            } else {
                if ($normalizedBearing >= $min && $normalizedBearing < $max) {
                    return $direction;
                }
            }
        }

        return 'N'; // Fallback
    }

    public function calculateMidpoint(Coordinates $from, Coordinates $to): Coordinates
    {
        $lat1 = deg2rad($from->latitude);
        $lon1 = deg2rad($from->longitude);
        $lat2 = deg2rad($to->latitude);
        $lon2 = deg2rad($to->longitude);

        $dLon = $lon2 - $lon1;

        $Bx = cos($lat2) * cos($dLon);
        $By = cos($lat2) * sin($dLon);

        $lat3 = atan2(
            sin($lat1) + sin($lat2),
            sqrt((cos($lat1) + $Bx) * (cos($lat1) + $Bx) + $By * $By)
        );
        $lon3 = $lon1 + atan2($By, cos($lat1) + $Bx);

        return new Coordinates(
            rad2deg($lat3),
            rad2deg($lon3)
        );
    }

    public function calculateDestination(
        Coordinates $from,
        float $distanceMeters,
        float $bearing
    ): Coordinates {
        $lat1 = deg2rad($from->latitude);
        $lon1 = deg2rad($from->longitude);
        $bearingRad = deg2rad($bearing);

        $angularDistance = $distanceMeters / self::EARTH_RADIUS_METERS;

        $lat2 = asin(
            sin($lat1) * cos($angularDistance) +
            cos($lat1) * sin($angularDistance) * cos($bearingRad)
        );

        $lon2 = $lon1 + atan2(
            sin($bearingRad) * sin($angularDistance) * cos($lat1),
            cos($angularDistance) - sin($lat1) * sin($lat2)
        );

        return new Coordinates(
            rad2deg($lat2),
            rad2deg($lon2)
        );
    }
}
