<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\Depreciation;

/**
 * Result DTO for depreciation run operations.
 *
 * Contains the results of a depreciation run including
 * assets processed, total depreciation, and journal entries.
 *
 * @since 1.0.0
 */
final readonly class DepreciationRunResult
{
    /**
     * @param bool $success Whether the depreciation run succeeded
     * @param string $runId Unique identifier for this run
     * @param int $assetsProcessed Number of assets processed
     * @param string $totalDepreciation Total depreciation amount (as string to avoid float precision issues)
     * @param array<string> $journalEntryIds IDs of generated journal entries
     * @param array<int, array{assetId: string, assetCode: string, assetName: string, depreciation: string, accumulatedDepreciation: string, netBookValue: string}> $assetDetails Detailed results per asset
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $runId,
        public int $assetsProcessed = 0,
        public string $totalDepreciation = '0',
        public array $journalEntries = [],
        public array $assetDetails = [],
        public ?string $error = null,
    ) {}
}
