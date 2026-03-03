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
    ) {}
}
