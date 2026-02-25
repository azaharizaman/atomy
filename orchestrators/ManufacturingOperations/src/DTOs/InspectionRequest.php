<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class InspectionRequest
{
    public function __construct(
        public string $orderId,
        public string $productId,
        public InspectionType $type,
        public ?string $operationId = null,
    ) {}
}
