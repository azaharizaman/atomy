<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;
use Nexus\FinanceOperations\Contracts\DepreciationManagerInterface;
use Nexus\FinanceOperations\Contracts\AssetQueryInterface;
use Nexus\FinanceOperations\Contracts\LedgerQueryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Run status constants for depreciation runs.
 */
final class DepreciationRunStatus
{
    public const string PENDING = 'pending';
    public const string RUNNING = 'running';
    public const string COMPLETED = 'completed';
    public const string FAILED = 'failed';
}

/**
 * Data provider for depreciation data aggregation.
 *
 * Aggregates data from:
 * - FixedAssetDepreciation package (depreciation runs, schedules)
 * - Assets package (asset master data, book values)
 * - JournalEntry package (accumulated depreciation GL)
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 *
 * @since 1.0.0
 */
final readonly class DepreciationDataProvider implements DepreciationDataProviderInterface
{
    public function __construct(
        private DepreciationManagerInterface $depreciationManager,
        private AssetQueryInterface $assetQuery,
        private ?LedgerQueryInterface $glQuery = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getDepreciationRunSummary(string $tenantId, string $periodId): array
    {
        $this->logger->debug('Fetching depreciation run summary', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        try {
            $runs = $this->depreciationManager->getRunsByPeriod($tenantId, $periodId);
            $runsArray = iterator_to_array($runs);

            $totalDepreciation = '0';
            $assetsProcessed = 0;
            $runDetails = [];

            foreach ($runsArray as $run) {
                $runDetails[] = [
                    'run_id' => $run->getId(),
                    'status' => $run->getStatus(),
                    'assets_count' => $run->getAssetsCount(),
                    'total_depreciation' => $run->getTotalDepreciation(),
                    'run_at' => $run->getRunAt()->format('Y-m-d H:i:s'),
                    'completed_at' => $run->getCompletedAt()?->format('Y-m-d H:i:s'),
                    'errors' => $run->getErrors(),
                ];
                $totalDepreciation = bcadd($totalDepreciation, $run->getTotalDepreciation(), 2);
                $assetsProcessed += $run->getAssetsCount();
            }

            return [
                'period_id' => $periodId,
                'runs_count' => count($runsArray),
                'total_depreciation' => $totalDepreciation,
                'assets_processed' => $assetsProcessed,
                'runs' => $runDetails,
                'has_pending_runs' => $this->hasPendingRuns($runsArray),
                'last_run_at' => $this->getLastRunAt($runsArray),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch depreciation run summary', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAssetBookValues(string $tenantId, array $assetIds): array
    {
        $this->logger->debug('Fetching asset book values', [
            'tenant_id' => $tenantId,
            'asset_count' => count($assetIds),
        ]);

        $result = [];

        foreach ($assetIds as $assetId) {
            // Default structure with all expected keys
            $assetResult = [
                'asset_id' => $assetId,
                'asset_code' => null,
                'asset_name' => null,
                'original_cost' => null,
                'accumulated_depreciation' => null,
                'book_value' => null,
                'currency' => null,
                'depreciation_method' => null,
                'useful_life' => null,
                'remaining_life' => null,
                'status' => null,
                'success' => false,
                'error' => null,
            ];

            try {
                $asset = $this->assetQuery->find($tenantId, $assetId);
                
                if ($asset === null) {
                    $assetResult['error'] = 'Asset not found';
                    $result[] = $assetResult;
                    continue;
                }

                $bookValue = $this->depreciationManager->getBookValue($tenantId, $assetId);

                $assetResult['asset_code'] = $asset->getCode();
                $assetResult['asset_name'] = $asset->getName();
                $assetResult['original_cost'] = $asset->getOriginalCost();
                $assetResult['accumulated_depreciation'] = $bookValue->getAccumulatedDepreciation();
                $assetResult['book_value'] = $bookValue->getNetBookValue();
                $assetResult['currency'] = $asset->getCurrency();
                $assetResult['depreciation_method'] = $asset->getDepreciationMethod();
                $assetResult['useful_life'] = $asset->getUsefulLife();
                $assetResult['remaining_life'] = $bookValue->getRemainingLife();
                $assetResult['status'] = $asset->getStatus();
                $assetResult['success'] = true;
                $assetResult['error'] = null;
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to fetch asset book value', [
                    'tenant_id' => $tenantId,
                    'asset_id' => $assetId,
                    'error' => $e->getMessage(),
                ]);
                $assetResult['error'] = $e->getMessage();
            }

            $result[] = $assetResult;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getDepreciationSchedules(string $tenantId, string $assetId): array
    {
        $this->logger->debug('Fetching depreciation schedules', [
            'tenant_id' => $tenantId,
            'asset_id' => $assetId,
        ]);

        try {
            $schedules = $this->depreciationManager->getSchedules($tenantId, $assetId);
            $result = [];

            foreach ($schedules as $schedule) {
                $result[] = [
                    'schedule_id' => $schedule->getId(),
                    'asset_id' => $schedule->getAssetId(),
                    'method' => $schedule->getMethod(),
                    'useful_life' => $schedule->getUsefulLife(),
                    'start_date' => $schedule->getStartDate()->format('Y-m-d'),
                    'end_date' => $schedule->getEndDate()->format('Y-m-d'),
                    'periods' => array_map(fn($p) => [
                        'period' => $p->getPeriod(),
                        'period_start' => $p->getPeriodStart()->format('Y-m-d'),
                        'period_end' => $p->getPeriodEnd()->format('Y-m-d'),
                        'depreciation' => $p->getDepreciationAmount(),
                        'accumulated' => $p->getAccumulatedDepreciation(),
                        'book_value' => $p->getBookValue(),
                        'is_posted' => $p->isPosted(),
                    ], $schedule->getPeriods()),
                    'total_periods' => count($schedule->getPeriods()),
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch depreciation schedules', [
                'tenant_id' => $tenantId,
                'asset_id' => $assetId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if there are pending runs.
     *
     * @param array<int, object> $runs
     */
    private function hasPendingRuns(array $runs): bool
    {
        foreach ($runs as $run) {
            $status = $run->getStatus();
            if ($status === DepreciationRunStatus::PENDING || $status === DepreciationRunStatus::RUNNING) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the last run timestamp.
     *
     * @param array<int, object> $runs
     */
    private function getLastRunAt(array $runs): ?string
    {
        $lastRun = null;
        foreach ($runs as $run) {
            $runAt = $run->getRunAt();
            if ($lastRun === null || $runAt > $lastRun) {
                $lastRun = $runAt;
            }
        }
        return $lastRun?->format('Y-m-d H:i:s');
    }
}
