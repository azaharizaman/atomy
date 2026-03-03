<?php

declare(strict_types=1);

namespace Nexus\Routing\Contracts;

use Nexus\Routing\ValueObjects\Coordinates;
use Nexus\Routing\ValueObjects\Distance;

interface DistanceCalculatorInterface
{
    public function calculate(Coordinates $from, Coordinates $to): Distance;
}
