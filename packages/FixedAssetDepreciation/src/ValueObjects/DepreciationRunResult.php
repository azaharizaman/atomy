<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\ValueObjects;

/**
 * Immutable value object representing the result of a batch depreciation run.
 *
 * This contains the aggregated results of running depreciation for
 * multiple assets in a period.
 *
 * @package Nexus\FixedAssetDepreciation\ValueObjects
 */
final readonly class DepreciationRunResult
{
    /**
     * @param string $periodId The period that was processed
     * @param \DateTimeImmutable $runDate When the run was executed
     * @param int $totalAssets Total number of assets processed
     * @param int $successCount Number of successful calculations
     * @param int $failureCount Number of failed calculations
     * @param float $totalDepreciation Total depreciation amount
     * @param array $successfulAssets List of successfully processed asset IDs
     * @param array $failedAssets List of failed assets with error messages
     * @param string|null $runId Unique identifier for this run
     * @param string|null $processedBy Who/what initiated the run
     */
    public function __construct(
        public string $periodId,
        public \DateTimeImmutable $runDate,
        public int $totalAssets,
        public int $successCount,
        public int $failureCount,
        public float $totalDepreciation,
        public array $successfulAssets,
        public array $failedAssets,
        public ?string $runId = null,
        public ?string $processedBy = null,
    ) {}

    /**
     * Create a depreciation run result.
     *
     * @param string $periodId The period ID
     * @param string $runId The run ID
     * @param array $processedAssets List of processed asset IDs
     * @param array $errors List of errors
     * @param string $currency The currency
     * @return self
     */
    public static function create(
        string $periodId,
        string $runId,
        array $processedAssets,
        array $errors,
        string $currency = 'USD'
    ): self {
        $failedAssets = [];
        foreach ($errors as $error) {
            $failedAssets[] = [
                'assetId' => $error['assetId'] ?? 'unknown',
                'error' => $error['message'] ?? 'Unknown error',
            ];
        }

        return new self(
            periodId: $periodId,
            runDate: new \DateTimeImmutable(),
            totalAssets: count($processedAssets) + count($errors),
            successCount: count($processedAssets),
            failureCount: count($errors),
            totalDepreciation: 0.0,
            successfulAssets: $processedAssets,
            failedAssets: $failedAssets,
            runId: $runId,
            processedBy: null,
        );
    }

    /**
     * Create a successful result.
     *
     * @param string $periodId The period ID
     * @param array $results Array of successful depreciation results
     * @param string|null $processedBy Who initiated the run
     * @return self
     */
    public static function success(
        string $periodId,
        array $results,
        ?string $processedBy = null
    ): self {
        $totalDepreciation = 0.0;
        $successfulAssets = [];

        foreach ($results as $result) {
            if (isset($result['assetId']) && isset($result['amount'])) {
                $successfulAssets[] = $result['assetId'];
                $totalDepreciation += $result['amount'];
            }
        }

        return new self(
            periodId: $periodId,
            runDate: new \DateTimeImmutable(),
            totalAssets: count($results),
            successCount: count($results),
            failureCount: 0,
            totalDepreciation: $totalDepreciation,
            successfulAssets: $successfulAssets,
            failedAssets: [],
            runId: uniqid('dep_run_'),
            processedBy: $processedBy,
        );
    }

    /**
     * Create a failed result.
     *
     * @param string $periodId The period ID
     * @param array $failures Array of failure details
     * @param string|null $processedBy Who initiated the run
     * @return self
     */
    public static function failure(
        string $periodId,
        array $failures,
        ?string $processedBy = null
    ): self {
        $failedAssets = [];

        foreach ($failures as $failure) {
            $failedAssets[] = [
                'assetId' => $failure['assetId'] ?? 'unknown',
                'error' => $failure['message'] ?? 'Unknown error',
            ];
        }

        return new self(
            periodId: $periodId,
            runDate: new \DateTimeImmutable(),
            totalAssets: count($failures),
            successCount: 0,
            failureCount: count($failures),
            totalDepreciation: 0.0,
            successfulAssets: [],
            failedAssets: $failedAssets,
            runId: uniqid('dep_run_'),
            processedBy: $processedBy,
        );
    }

    /**
     * Check if the run was successful.
     *
     * @return bool True if there were no failures
     */
    public function isSuccessful(): bool
    {
        return $this->failureCount === 0;
    }

    /**
     * Check if there were any failures.
     *
     * @return bool True if there were failures
     */
    public function hasFailures(): bool
    {
        return $this->failureCount > 0;
    }

    /**
     * Get the success rate.
     *
     * @return float The success rate as a percentage (0-100)
     */
    public function getSuccessRate(): float
    {
        if ($this->totalAssets === 0) {
            return 100.0;
        }
        return ($this->successCount / $this->totalAssets) * 100;
    }

    /**
     * Get average depreciation per asset.
     *
     * @return float Average depreciation amount
     */
    public function getAverageDepreciation(): float
    {
        if ($this->successCount === 0) {
            return 0.0;
        }
        return $this->totalDepreciation / $this->successCount;
    }

    /**
     * Get summary statistics.
     *
     * @return array{
     *     periodId: string,
     *     runDate: string,
     *     totalAssets: int,
     *     successCount: int,
     *     failureCount: int,
     *     successRate: float,
     *     totalDepreciation: float,
     *     averageDepreciation: float,
     *     runId: string|null,
     *     processedBy: string|null
     * }
     */
    public function getSummary(): array
    {
        return [
            'periodId' => $this->periodId,
            'runDate' => $this->runDate->format('Y-m-d H:i:s'),
            'totalAssets' => $this->totalAssets,
            'successCount' => $this->successCount,
            'failureCount' => $this->failureCount,
            'successRate' => $this->getSuccessRate(),
            'totalDepreciation' => $this->totalDepreciation,
            'averageDepreciation' => $this->getAverageDepreciation(),
            'runId' => $this->runId,
            'processedBy' => $this->processedBy,
        ];
    }

    /**
     * Format as human-readable string.
     *
     * @return string
     */
    public function format(): string
    {
        return sprintf(
            'DepreciationRun: Period=%s, Total=%d, Success=%d, Failed=%d, TotalDep=%.2f',
            $this->periodId,
            $this->totalAssets,
            $this->successCount,
            $this->failureCount,
            $this->totalDepreciation
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'periodId' => $this->periodId,
            'runDate' => $this->runDate->format('Y-m-d H:i:s'),
            'totalAssets' => $this->totalAssets,
            'successCount' => $this->successCount,
            'failureCount' => $this->failureCount,
            'successRate' => $this->getSuccessRate(),
            'totalDepreciation' => $this->totalDepreciation,
            'averageDepreciation' => $this->getAverageDepreciation(),
            'successfulAssets' => $this->successfulAssets,
            'failedAssets' => $this->failedAssets,
            'runId' => $this->runId,
            'processedBy' => $this->processedBy,
        ];
    }
}
