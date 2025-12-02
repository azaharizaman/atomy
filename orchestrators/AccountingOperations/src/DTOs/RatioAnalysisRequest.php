<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DTOs;

use Nexus\FinancialRatios\Enums\RatioCategory;

/**
 * Request DTO for ratio analysis operations.
 */
final readonly class RatioAnalysisRequest
{
    /**
     * @param array<RatioCategory> $categories
     * @param array<string> $comparativePeriodIds
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $categories = [],
        public array $comparativePeriodIds = [],
        public bool $includeBenchmarks = true,
        public ?string $industryCode = null,
        public bool $includeHealthIndicators = true,
        public array $options = [],
    ) {}
}
