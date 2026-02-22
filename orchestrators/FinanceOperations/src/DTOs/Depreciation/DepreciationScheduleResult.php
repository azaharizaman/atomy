<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\Depreciation;

/**
 * Result DTO for depreciation schedule generation.
 *
 * Contains the generated depreciation schedule showing
 * periodic depreciation over the asset's useful life.
 *
 * @since 1.0.0
 */
final readonly class DepreciationScheduleResult
{
    /**
     * @param bool $success Whether the schedule generation succeeded
     * @param string $assetId Asset identifier
     * @param array<int, array{period: string, periodStart: string, periodEnd: string, depreciation: string, accumulatedDepreciation: string, netBookValue: string}> $schedule Depreciation schedule by period
     * @param string $totalDepreciation Total depreciation over life (as string to avoid float precision issues)
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $assetId,
        public array $schedule = [],
        public string $totalDepreciation = '0',
        public ?string $error = null,
    ) {}
}
