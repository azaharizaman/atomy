<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\Depreciation;

/**
 * Request DTO for asset revaluation operations.
 *
 * Used to revalue an asset to a new carrying amount,
 * with automatic posting of revaluation entries.
 *
 * @since 1.0.0
 */
final readonly class RevaluationRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $assetId Asset to revalue
     * @param string $newValue New asset value (as string to avoid float precision issues)
     * @param string $reason Reason for revaluation
     * @param \DateTimeImmutable $effectiveDate Effective date of revaluation
     * @param bool $postToGL Post revaluation entry to GL
     */
    public function __construct(
        public string $tenantId,
        public string $assetId,
        public string $newValue,
        public string $reason,
        public \DateTimeImmutable $effectiveDate,
        public bool $postToGL = true,
    ) {}
}
