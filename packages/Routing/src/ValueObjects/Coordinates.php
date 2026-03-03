<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

final readonly class Coordinates
{
    public const float EARTH_RADIUS = 6371000.0;

    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if (!is_finite($latitude)) {
            throw new \InvalidArgumentException('Latitude must be a finite number.');
        }

        if (!is_finite($longitude)) {
            throw new \InvalidArgumentException('Longitude must be a finite number.');
        }

        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new \InvalidArgumentException('Latitude must be between -90 and 90 degrees.');
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new \InvalidArgumentException('Longitude must be between -180 and 180 degrees.');
        }
    }

    public function distanceTo(self $other): Distance
    {
        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($other->latitude);
        $deltaLat = deg2rad($other->latitude - $this->latitude);
        $deltaLon = deg2rad($other->longitude - $this->longitude);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
            + cos($lat1) * cos($lat2)
            * sin($deltaLon / 2) * sin($deltaLon / 2);
        $a = max(0.0, min(1.0, $a));
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return new Distance(self::EARTH_RADIUS * $c);
    }
}
