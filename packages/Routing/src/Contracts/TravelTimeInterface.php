<?php

declare(strict_types=1);

namespace Nexus\Routing\Contracts;

use Nexus\Routing\ValueObjects\Coordinates;

interface TravelTimeInterface
{
    public function estimateTravelTime(Coordinates $from, Coordinates $to, string $mode): int;
}
