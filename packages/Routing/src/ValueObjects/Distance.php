<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

final readonly class Distance
{
    public function __construct(public float $meters) {}

    public function add(self $other): self
    {
        return new self($this->meters + $other->meters);
    }

    public function toMeters(): float
    {
        return $this->meters;
    }
}
