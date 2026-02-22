<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for depreciation run operations.
 */
final readonly class DepreciationRunRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $assetIds = [],
        public bool $postToGL = true,
        public bool $validateOnly = false,
    ) {}
}
