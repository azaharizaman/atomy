<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\Depreciation;

/**
 * Request DTO for depreciation run operations.
 *
 * Used to execute depreciation calculations for assets
 * within a specific accounting period.
 *
 * @since 1.0.0
 */
final readonly class DepreciationRunRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for depreciation
     * @param array<string> $assetIds Specific assets to process (empty = all assets)
     * @param bool $postToGL Post depreciation entries to GL
     * @param bool $validateOnly Validate without posting
     * @param string|null $depreciationBook Depreciation book for Tier 3 multi-book support
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $assetIds = [],
        public bool $postToGL = true,
        public bool $validateOnly = false,
        public ?string $depreciationBook = null,
    ) {}
}
