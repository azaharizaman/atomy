<?php

declare(strict_types=1);

namespace Nexus\Warehouse\ValueObjects;

use Nexus\Warehouse\Contracts\PickRouteResult;

/**
 * Pick route result value object
 */
final readonly class PickRouteResultValue implements PickRouteResult
{
    public function __construct(
        private array $optimizedSequence,
        private float $totalDistance,
        private float $distanceImprovement,
        private int $executionTime
    ) {
    }
    
    public function getOptimizedSequence(): array
    {
        return $this->optimizedSequence;
    }
    
    public function getTotalDistance(): float
    {
        return $this->totalDistance;
    }
    
    public function getDistanceImprovement(): float
    {
        return $this->distanceImprovement;
    }
    
    public function getExecutionTime(): int
    {
        return $this->executionTime;
    }
}
