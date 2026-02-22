<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
        private object $depreciationManager,  // DepreciationManagerInterface
        private object $assetQuery,  // AssetQueryInterface
        private ?object $glQuery = null,  // LedgerQueryInterface
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

            $totalDepreciation = '0';
            $assetsProcessed = 0;
            $runDetails = [];

            foreach ($runs as $run) {
                $runDetails[] = [
                    'run_id' => $run->getId(),
                    'status' => $run->getStatus(),
                    'assets_count' => $run->getAssetsCount(),
                    'total_depreciation' => $run->getTotalDepreciation(),
                    'run_at' => $run->getRunAt()->format('Y-m-d H:i:s'),
                    'completed_at' => $run->getCompletedAt()?->format('Y-m-d H:i:s'),
                    'errors' => $run->getErrors(),
                ];
                $totalDepreciation = (string)((float) $totalDepreciation + (float) $run->getTotalDepreciation());
                $assetsProcessed += $run->getAssetsCount();
            }

            return [
                'period_id' => $periodId,
                'runs_count' => count($runs),
                'total_depreciation' => $totalDepreciation,
                'assets_processed' => $assetsProcessed,
                'runs' => $runDetails,
                'has_pending_runs' => $this->hasPendingRuns($runs),
                'last_run_at' => $this->getLastRunAt($runs),
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
            try {
                $asset = $this->assetQuery->find($tenantId, $assetId);
                $bookValue = $this->depreciationManager->getBookValue($tenantId, $assetId);

                $result[] = [
                    'asset_id' => $assetId,
                    'asset_code' => $asset->getCode(),
                    'asset_name' => $asset->getName(),
                    'original_cost' => $asset->getOriginalCost(),
                    'accumulated_depreciation' => $bookValue->getAccumulatedDepreciation(),
                    'book_value' => $bookValue->getNetBookValue(),
                    'currency' => $asset->getCurrency(),
                    'depreciation_method' => $asset->getDepreciationMethod(),
                    'useful_life' => $asset->getUsefulLife(),
                    'remaining_life' => $bookValue->getRemainingLife(),
                    'status' => $asset->getStatus(),
                ];
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to fetch asset book value', [
                    'tenant_id' => $tenantId,
                    'asset_id' => $assetId,
                    'error' => $e->getMessage(),
                ]);

                $result[] = [
                    'asset_id' => $assetId,
                    'error' => $e->getMessage(),
                ];
            }
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
            if ($run->getStatus() === 'pending' || $run->getStatus() === 'running') {
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
