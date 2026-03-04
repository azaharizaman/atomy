<?php

declare(strict_types=1);

namespace Nexus\Warehouse\ValueObjects;

final readonly class Distance
{
    public function __construct(public float $meters) {}

    public function toMeters(): float
    {
        return $this->meters;
    }
}
