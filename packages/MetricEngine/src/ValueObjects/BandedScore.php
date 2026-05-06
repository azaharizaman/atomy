<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class BandedScore
{
    public function __construct(
        public float $value,
        public string $bandLabel
    ) {}
}
