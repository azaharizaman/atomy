<?php

declare(strict_types=1);

namespace Nexus\Geo\Services;

use Nexus\Geo\Contracts\DistanceCalculatorInterface;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\Distance;

/**
 * Stateless distance calculator using Haversine formula
 * 
 * Provides great-circle distance calculations
 */
final readonly class DistanceCalculator implements DistanceCalculatorInterface
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function calculate(Coordinates $from, Coordinates $to): Distance
    {
        $latFrom = deg2rad($from->latitude);
        $lonFrom = deg2rad($from->longitude);
        $latTo = deg2rad($to->latitude);
        $lonTo = deg2rad($to->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $meters = self::EARTH_RADIUS_METERS * $c;

        return new Distance($meters);
    }

    public function calculateBatch(Coordinates $from, array $destinations): array
    {
        return array_map(
            fn(Coordinates $destination) => $this->calculate($from, $destination),
            $destinations
        );
    }

    public function findNearest(Coordinates $from, array $candidates): array
    {
        if (empty($candidates)) {
            throw new \InvalidArgumentException('Candidates array cannot be empty');
        }

        $nearest = null;
        $nearestIndex = -1;
        $nearestDistance = null;

        foreach ($candidates as $index => $candidate) {
            $distance = $this->calculate($from, $candidate);

            if ($nearestDistance === null || $distance->meters < $nearestDistance->meters) {
                $nearest = $candidate;
                $nearestIndex = $index;
                $nearestDistance = $distance;
            }
        }

        return [
            'index' => $nearestIndex,
            'coordinates' => $nearest,
            'distance' => $nearestDistance,
        ];
    }

    public function sortByDistance(Coordinates $from, array $coordinates): array
    {
        $withDistances = array_map(
            fn(Coordinates $coord) => [
                'coordinates' => $coord,
                'distance' => $this->calculate($from, $coord),
            ],
            $coordinates
        );

        usort($withDistances, fn($a, $b) => $a['distance']->meters <=> $b['distance']->meters);

        return $withDistances;
    }
}
