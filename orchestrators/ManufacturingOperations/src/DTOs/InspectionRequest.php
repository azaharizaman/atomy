<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class InspectionRequest
{
    public function __construct(
        public string $orderId,
        public string $productId,
        public string $type, // 'FirstArticle', 'InProcess', 'Final'
        public ?string $operationId = null,
    ) {}
}
