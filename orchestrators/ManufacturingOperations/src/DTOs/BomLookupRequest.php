<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class BomLookupRequest
{
    public function __construct(
        public string $productId,
        public ?string $bomId = null,
        public ?string $revision = null,
    ) {}
}
