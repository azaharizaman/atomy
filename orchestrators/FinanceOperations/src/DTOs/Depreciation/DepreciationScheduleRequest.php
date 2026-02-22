<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\Depreciation;

/**
 * Request DTO for depreciation schedule generation.
 *
 * Used to generate a depreciation schedule for an asset
 * based on specified depreciation method and parameters.
 *
 * @since 1.0.0
 */
final readonly class DepreciationScheduleRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $assetId Asset to generate schedule for
     * @param string $depreciationMethod Method: 'straight_line', 'declining_balance', 'sum_of_years'
     * @param int $usefulLifeYears Useful life in years
     * @param string $salvageValue Salvage value at end of life (as string to avoid float precision issues)
     * @param string|null $originalCost Original cost of the asset (optional - will be fetched from asset data if not provided)
     */
    public function __construct(
        public string $tenantId,
        public string $assetId,
        public string $depreciationMethod = 'straight_line',
        public int $usefulLifeYears = 5,
        public string $salvageValue = '0',
        public ?string $originalCost = null,
    ) {}
}
