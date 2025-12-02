<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DTOs;

use Nexus\AccountVarianceAnalysis\Enums\VarianceType;

/**
 * Request DTO for variance report generation.
 */
final readonly class VarianceReportRequest
{
    /**
     * @param array<string> $accountIds
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public VarianceType $varianceType,
        public string $comparativePeriodId,
        public ?string $budgetId = null,
        public array $accountIds = [],
        public bool $includeAttribution = true,
        public bool $includeTrends = true,
        public array $options = [],
    ) {}
}
