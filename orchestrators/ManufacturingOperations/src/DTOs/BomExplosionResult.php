<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class BomExplosionResult
{
    /**
     * @param array<string, float> $components Map of component Product ID to Quantity
     */
    public function __construct(
        public string $bomId,
        public string $productId,
        public array $components,
        public string $revision,
    ) {}
}
