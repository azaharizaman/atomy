<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

use Nexus\Warehouse\ValueObjects\Coordinates;
use Nexus\Warehouse\ValueObjects\RouteStop;

interface RouteOptimizerInterface
{
    /**
     * @param array<RouteStop> $stops
     */
    public function optimizeTsp(array $stops, Coordinates $depotCoordinates): RouteOptimizationResultInterface;
}
