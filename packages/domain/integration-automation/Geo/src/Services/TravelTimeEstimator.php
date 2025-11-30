<?php

declare(strict_types=1);

namespace Nexus\Geo\Services;

use Nexus\Geo\Contracts\TravelTimeInterface;
use Nexus\Geo\Contracts\DistanceCalculatorInterface;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\TravelTimeMatrix;
use Nexus\Geo\ValueObjects\Distance;

/**
 * Stateless travel time estimator
 * 
 * Provides simple travel time estimates based on straight-line distance
 * For production, use external APIs (Google Maps Distance Matrix)
 */
final readonly class TravelTimeEstimator implements TravelTimeInterface
{
    private const AVERAGE_SPEEDS = [
        'driving' => 13.89,   // 50 km/h in m/s
        'walking' => 1.39,    // 5 km/h in m/s
        'cycling' => 4.17,    // 15 km/h in m/s
    ];

    public function __construct(
        private DistanceCalculatorInterface $distanceCalculator
    ) {
    }

    public function calculateMatrix(
        array $origins,
        array $destinations,
        array $options = []
    ): TravelTimeMatrix {
        $mode = $options['mode'] ?? 'driving';
        $matrix = [];

        foreach ($origins as $fromId => $fromCoords) {
            foreach ($destinations as $toId => $toCoords) {
                $distance = $this->distanceCalculator->calculate($fromCoords, $toCoords);
                $duration = $this->estimateTravelTime($fromCoords, $toCoords, $mode);

                $matrix[$fromId][$toId] = [
                    'distance' => $distance,
                    'duration' => $duration,
                ];
            }
        }

        return new TravelTimeMatrix($matrix);
    }

    public function estimateTravelTime(
        Coordinates $from,
        Coordinates $to,
        string $mode = 'driving'
    ): int {
        $distance = $this->distanceCalculator->calculate($from, $to);
        $speed = $this->getAverageSpeed($mode);

        // Time = Distance / Speed
        return (int) round($distance->meters / $speed);
    }

    public function getAverageSpeed(string $mode): float
    {
        if (!isset(self::AVERAGE_SPEEDS[$mode])) {
            throw new \InvalidArgumentException("Invalid travel mode: {$mode}");
        }

        return self::AVERAGE_SPEEDS[$mode];
    }
}
