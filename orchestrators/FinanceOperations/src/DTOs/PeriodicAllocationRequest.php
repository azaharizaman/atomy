<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for periodic allocation operations.
 */
final readonly class PeriodicAllocationRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $options = [],
    ) {}
}
