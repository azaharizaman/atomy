<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for depreciation schedule operations.
 */
final readonly class DepreciationScheduleRequest
{
    public function __construct(
        public string $tenantId,
        public string $assetId,
        public array $options = [],
    ) {}
}
