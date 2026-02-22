<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for consistency check operations.
 */
final readonly class ConsistencyCheckRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $options = [],
    ) {}
}
