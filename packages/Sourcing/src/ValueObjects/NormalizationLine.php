<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

final readonly class NormalizationLine
{
    public function __construct(
        public string $id,
        public string $description,
        public float $quantity,
        public string $uom,
        public float $unitPrice,
        public ?string $rfqLineId = null,
    ) {
    }
}
