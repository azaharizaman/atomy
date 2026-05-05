<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class ComparisonResult
{
    public function __construct(
        public int|float $delta,
        public float $percentChange
    ) {}
}
