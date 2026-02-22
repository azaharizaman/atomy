<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for product cost operations.
 */
final readonly class ProductCostRequest
{
    public function __construct(
        public string $tenantId,
        public string $productId,
        public array $options = [],
    ) {}
}
