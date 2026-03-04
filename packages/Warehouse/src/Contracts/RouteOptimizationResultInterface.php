<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

use Nexus\Warehouse\ValueObjects\RouteStop;

interface RouteOptimizationResultInterface
{
    /**
     * @return array<RouteStop>
     */
    public function getStops(): array;

    public function getTotalDistanceMeters(): float;
}
