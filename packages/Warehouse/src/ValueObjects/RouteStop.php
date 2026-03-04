<?php

declare(strict_types=1);

namespace Nexus\Warehouse\ValueObjects;

final readonly class RouteStop
{
    public function __construct(
        public string $id,
        public Coordinates $coordinates,
        public ?\DateTimeImmutable $timeWindowStart = null,
        public ?\DateTimeImmutable $timeWindowEnd = null,
        public int $serviceTimeSeconds = 60,
    ) {
        if (trim($id) === '') {
            throw new \InvalidArgumentException('RouteStop id must be a non-empty string.');
        }

        if ($serviceTimeSeconds < 0) {
            throw new \InvalidArgumentException('RouteStop serviceTimeSeconds must be >= 0.');
        }

        if (
            $timeWindowStart !== null
            && $timeWindowEnd !== null
            && $timeWindowStart > $timeWindowEnd
        ) {
            throw new \InvalidArgumentException('RouteStop timeWindowStart must be less than or equal to timeWindowEnd.');
        }
    }
}
