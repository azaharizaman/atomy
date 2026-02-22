<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationRunRequest;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationRunResult;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationScheduleRequest;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationScheduleResult;
use Nexus\FinanceOperations\Exceptions\DepreciationCoordinationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for depreciation run operations.
 * 
 * This service handles:
 * - Depreciation calculation execution
 * - Schedule generation
 * - Book value tracking
 * 
 * Following Advanced Orchestrator Pattern v1.1:
 * Services handle the "heavy lifting" - calculations and cross-boundary logic.
 * 
 * @since 1.0.0
 */
final readonly class DepreciationRunService
{
    public function __construct(
        private DepreciationDataProviderInterface $dataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Execute depreciation run for a period.
     *
     * @param DepreciationRunRequest $request The depreciation run request parameters
     * @return DepreciationRunResult The run result
     * @throws DepreciationCoordinationException If run fails
     */
    public function executeRun(DepreciationRunRequest $request): DepreciationRunResult
    {
        $this->logger->info('Executing depreciation run', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'asset_count' => count($request->assetIds),
            'validate_only' => $request->validateOnly,
        ]);

        try {
            // Get assets to process
            $assetIds = $request->assetIds;
            if (empty($assetIds)) {
                // Get all active assets for the period from summary
                $summary = $this->dataProvider->getDepreciationRunSummary(
                    $request->tenantId,
                    $request->periodId
                );
                $assetIds = $summary['active_asset_ids'] ?? [];
            }

            if (empty($assetIds)) {
                return new DepreciationRunResult(
                    success: true,
                    runId: $this->generateRunId(),
                    assetsProcessed: 0,
                    totalDepreciation: '0',
                    journalEntries: [],
                    assetDetails: [],
                );
            }

            // Get book values for all assets
            $bookValuesData = $this->dataProvider->getAssetBookValues($request->tenantId, $assetIds);
            $bookValues = $bookValuesData['assets'] ?? $bookValuesData;

            $totalDepreciation = 0.0;
            $assetDetails = [];
            $journalEntries = [];

            foreach ($bookValues as $assetData) {
                $depreciation = $this->calculateDepreciation($assetData);

                if ($depreciation > 0) {
                    $totalDepreciation += $depreciation;
                    $assetDetails[] = [
                        'assetId' => $assetData['asset_id'] ?? $assetData['id'] ?? '',
                        'assetCode' => $assetData['asset_code'] ?? $assetData['code'] ?? '',
                        'assetName' => $assetData['asset_name'] ?? $assetData['name'] ?? '',
                        'depreciation' => (string)$depreciation,
                        'accumulatedDepreciation' => (string)(
                            (float)($assetData['accumulated_depreciation'] ?? 0) + $depreciation
                        ),
                        'netBookValue' => (string)((float)($assetData['book_value'] ?? 0) - $depreciation),
                    ];

                    if ($request->postToGL && !$request->validateOnly) {
                        $journalEntries[] = [
                            'asset_id' => $assetData['asset_id'] ?? $assetData['id'] ?? '',
                            'depreciation_account' => $this->getDepreciationAccount($assetData),
                            'accumulated_account' => $this->getAccumulatedDepreciationAccount($assetData),
                            'amount' => (string)$depreciation,
                        ];
                    }
                }
            }

            return new DepreciationRunResult(
                success: true,
                runId: $this->generateRunId(),
                assetsProcessed: count($assetDetails),
                totalDepreciation: (string)round($totalDepreciation, 2),
                journalEntries: $journalEntries,
                assetDetails: $assetDetails,
            );
        } catch (DepreciationCoordinationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Depreciation run failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw DepreciationCoordinationException::runFailed(
                $request->tenantId,
                $request->periodId,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Generate depreciation schedule for an asset.
     *
     * @param DepreciationScheduleRequest $request The schedule request parameters
     * @return DepreciationScheduleResult The generated schedule
     * @throws DepreciationCoordinationException If schedule generation fails
     */
    public function generateSchedule(DepreciationScheduleRequest $request): DepreciationScheduleResult
    {
        $this->logger->info('Generating depreciation schedule', [
            'asset_id' => $request->assetId,
            'method' => $request->depreciationMethod,
            'useful_life' => $request->usefulLifeYears,
        ]);

        try {
            $existingSchedules = $this->dataProvider->getDepreciationSchedules(
                $request->tenantId,
                $request->assetId
            );

            // Calculate schedule periods
            $schedule = $this->calculateSchedulePeriods($request);
            $totalDepreciation = array_sum(array_column($schedule, 'depreciation'));

            return new DepreciationScheduleResult(
                success: true,
                assetId: $request->assetId,
                schedule: $schedule,
                totalDepreciation: (string)round($totalDepreciation, 2),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Schedule generation failed', [
                'asset_id' => $request->assetId,
                'error' => $e->getMessage(),
            ]);

            throw DepreciationCoordinationException::scheduleGenerationFailed(
                $request->tenantId,
                $request->assetId,
                $e->getMessage()
            );
        }
    }

    /**
     * Get depreciation run summary for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Run summary data
     */
    public function getRunSummary(string $tenantId, string $periodId): array
    {
        $this->logger->info('Getting depreciation run summary', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        return $this->dataProvider->getDepreciationRunSummary($tenantId, $periodId);
    }

    /**
     * Calculate depreciation for a single asset.
     *
     * @param array<string, mixed> $assetData Asset data including book values
     * @return float Calculated depreciation amount
     */
    private function calculateDepreciation(array $assetData): float
    {
        $bookValue = (float)($assetData['book_value'] ?? $assetData['net_book_value'] ?? 0);
        $accumulatedDepreciation = (float)($assetData['accumulated_depreciation'] ?? 0);
        $originalCost = (float)($assetData['original_cost'] ?? $assetData['cost'] ?? 0);
        $salvageValue = (float)($assetData['salvage_value'] ?? 0);
        $usefulLifeMonths = (int)($assetData['useful_life_months'] ?? 12);
        $method = $assetData['depreciation_method'] ?? 'straight_line';

        // Check if fully depreciated
        if ($bookValue <= $salvageValue) {
            return 0.0;
        }

        $depreciableAmount = $originalCost - $salvageValue;
        $remainingAmount = $depreciableAmount - $accumulatedDepreciation;

        if ($remainingAmount <= 0) {
            return 0.0;
        }

        switch ($method) {
            case 'straight_line':
                // Monthly depreciation
                $monthlyDepreciation = $depreciableAmount / $usefulLifeMonths;
                return min($monthlyDepreciation, $remainingAmount);

            case 'declining_balance':
                $rate = (float)($assetData['declining_rate'] ?? 2.0) / $usefulLifeMonths;
                $depreciation = $bookValue * $rate;
                return min($depreciation, $remainingAmount);

            case 'sum_of_years':
                $years = (int)($usefulLifeMonths / 12);
                $sumOfYears = ($years * ($years + 1)) / 2;
                $currentYear = (int)(($accumulatedDepreciation / ($depreciableAmount / $years)) + 1);
                $yearDepreciation = ($depreciableAmount * ($years - $currentYear + 1)) / $sumOfYears;
                return min($yearDepreciation / 12, $remainingAmount);

            default:
                // Default to straight line
                $monthlyDepreciation = $depreciableAmount / $usefulLifeMonths;
                return min($monthlyDepreciation, $remainingAmount);
        }
    }

    /**
     * Calculate schedule periods.
     *
     * @param DepreciationScheduleRequest $request The schedule request
     * @return array<int, array{period: string, periodStart: string, periodEnd: string, depreciation: float, accumulatedDepreciation: float, netBookValue: float}> Schedule data
     */
    private function calculateSchedulePeriods(DepreciationScheduleRequest $request): array
    {
        $schedule = [];
        $salvageValue = (float)$request->salvageValue;
        $usefulLifeYears = $request->usefulLifeYears;
        
        // Use provided original cost if available, otherwise use a calculated default
        // The default calculation (salvage * 2) is a workaround for missing asset data
        // In production, originalCost should always be provided via asset data provider
        if ($request->originalCost !== null) {
            $originalCost = (float)$request->originalCost;
        } else {
            $this->logger->warning(
                'Original cost not provided in depreciation schedule request. ' .
                'Using salvage value * 2 as placeholder. ' .
                'Provide originalCost for accurate depreciation calculation.',
                [
                    'asset_id' => $request->assetId,
                    'salvage_value' => $request->salvageValue,
                ]
            );
            $originalCost = $salvageValue * 2;
        }
        
        $depreciableAmount = $originalCost - $salvageValue;
        $annualDepreciation = $depreciableAmount / $usefulLifeYears;
        $monthlyDepreciation = $annualDepreciation / 12;

        $accumulated = 0.0;
        $startDate = new \DateTimeImmutable();

        for ($year = 1; $year <= $usefulLifeYears; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $periodStart = $startDate->modify(sprintf('+%d months', ($year - 1) * 12 + $month - 1));
                $periodEnd = $periodStart->modify('last day of this month');
                
                $accumulated += $monthlyDepreciation;
                $netBookValue = $originalCost - $accumulated;
                
                $schedule[] = [
                    'period' => sprintf('Y%dM%02d', $year, $month),
                    'periodStart' => $periodStart->format('Y-m-d'),
                    'periodEnd' => $periodEnd->format('Y-m-d'),
                    'depreciation' => round($monthlyDepreciation, 2),
                    'accumulatedDepreciation' => round($accumulated, 2),
                    'netBookValue' => round(max(0, $netBookValue), 2),
                ];
            }
        }

        return $schedule;
    }

    /**
     * Get depreciation expense account for asset.
     *
     * @param array<string, mixed> $assetData Asset data
     * @return string Account code
     */
    private function getDepreciationAccount(array $assetData): string
    {
        return $assetData['depreciation_account'] ?? $assetData['depreciation_expense_account'] ?? 'DEPRECIATION_EXPENSE';
    }

    /**
     * Get accumulated depreciation account for asset.
     *
     * @param array<string, mixed> $assetData Asset data
     * @return string Account code
     */
    private function getAccumulatedDepreciationAccount(array $assetData): string
    {
        return $assetData['accumulated_account'] ?? $assetData['accumulated_depreciation_account'] ?? 'ACCUMULATED_DEPRECIATION';
    }

    /**
     * Generate a unique run ID.
     *
     * @return string Unique run identifier
     */
    private function generateRunId(): string
    {
        return 'DEPR-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    }
}
