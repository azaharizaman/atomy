<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Engine;

use Nexus\Accounting\Core\ValueObjects\{ReportingPeriod, VarianceAnalysis};
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Nexus\Analytics\Contracts\BudgetRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Variance calculation engine.
 *
 * Compares actuals vs budget and identifies deviations.
 */
final readonly class VarianceCalculator
{
    public function __construct(
        private LedgerRepositoryInterface $ledgerRepository,
        private BudgetRepositoryInterface $budgetRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Calculate variance for a single account.
     */
    public function calculateAccountVariance(
        string $accountId,
        ReportingPeriod $period,
        ?string $notes = null
    ): VarianceAnalysis {
        $this->logger->debug('Calculating variance', [
            'account_id' => $accountId,
            'period' => $period->getLabel(),
        ]);

        // Get actual amount
        $actual = $this->ledgerRepository->getAccountBalance($accountId, $period->getEndDate());

        // Get budget amount
        $budget = $this->budgetRepository->getBudgetAmount(
            $accountId,
            $period->getStartDate(),
            $period->getEndDate()
        );

        // Get account details
        $account = $this->ledgerRepository->getAccountById($accountId);

        return new VarianceAnalysis(
            accountId: $accountId,
            accountName: $account['name'] ?? 'Unknown',
            actualAmount: $actual,
            budgetAmount: $budget,
            period: $period,
            notes: $notes
        );
    }

    /**
     * Calculate variances for multiple accounts.
     *
     * @param string[] $accountIds
     * @return VarianceAnalysis[]
     */
    public function calculateMultipleVariances(
        array $accountIds,
        ReportingPeriod $period
    ): array {
        $variances = [];

        foreach ($accountIds as $accountId) {
            try {
                $variances[] = $this->calculateAccountVariance($accountId, $period);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to calculate variance', [
                    'account_id' => $accountId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $variances;
    }

    /**
     * Calculate variances for all accounts in an entity.
     *
     * @return VarianceAnalysis[]
     */
    public function calculateEntityVariances(
        string $entityId,
        ReportingPeriod $period,
        ?array $accountTypes = null
    ): array {
        $accounts = $this->ledgerRepository->getAccountsByEntity($entityId, $accountTypes);
        $accountIds = array_column($accounts, 'id');

        return $this->calculateMultipleVariances($accountIds, $period);
    }

    /**
     * Get significant variances only.
     *
     * @param VarianceAnalysis[] $variances
     * @return VarianceAnalysis[]
     */
    public function filterSignificantVariances(
        array $variances,
        float $thresholdPercentage = 10.0,
        ?float $thresholdAmount = null
    ): array {
        return array_filter($variances, function(VarianceAnalysis $variance) use ($thresholdPercentage, $thresholdAmount) {
            $isSignificantPercentage = $variance->isSignificant($thresholdPercentage);
            
            if ($thresholdAmount === null) {
                return $isSignificantPercentage;
            }

            $isSignificantAmount = abs($variance->getVarianceAmount()) >= $thresholdAmount;
            
            return $isSignificantPercentage && $isSignificantAmount;
        });
    }

    /**
     * Calculate variance trend across multiple periods.
     *
     * @param ReportingPeriod[] $periods
     * @return array<string, mixed>
     */
    public function calculateVarianceTrend(
        string $accountId,
        array $periods
    ): array {
        $trendData = [];

        foreach ($periods as $period) {
            $variance = $this->calculateAccountVariance($accountId, $period);
            $trendData[] = [
                'period' => $period->getLabel(),
                'actual' => $variance->getActualAmount(),
                'budget' => $variance->getBudgetAmount(),
                'variance_amount' => $variance->getVarianceAmount(),
                'variance_percentage' => $variance->getVariancePercentage(),
            ];
        }

        return [
            'account_id' => $accountId,
            'periods' => $trendData,
            'average_variance' => $this->calculateAverageTrend($trendData),
        ];
    }

    /**
     * Generate variance summary report.
     *
     * @return array<string, mixed>
     */
    public function generateVarianceSummary(
        string $entityId,
        ReportingPeriod $period,
        float $significanceThreshold = 10.0
    ): array {
        $variances = $this->calculateEntityVariances($entityId, $period);
        $significant = $this->filterSignificantVariances($variances, $significanceThreshold);

        $favorable = [];
        $unfavorable = [];

        foreach ($significant as $variance) {
            if ($variance->isFavorable()) {
                $favorable[] = $variance;
            } else {
                $unfavorable[] = $variance;
            }
        }

        return [
            'entity_id' => $entityId,
            'period' => $period->toArray(),
            'total_accounts' => count($variances),
            'significant_variances' => count($significant),
            'favorable_count' => count($favorable),
            'unfavorable_count' => count($unfavorable),
            'total_variance_amount' => array_sum(array_map(
                fn($v) => $v->getVarianceAmount(),
                $variances
            )),
            'favorable' => array_map(fn($v) => $v->toArray(), $favorable),
            'unfavorable' => array_map(fn($v) => $v->toArray(), $unfavorable),
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * Calculate average trend.
     *
     * @param array<string, mixed> $trendData
     */
    private function calculateAverageTrend(array $trendData): float
    {
        if (empty($trendData)) {
            return 0.0;
        }

        $sum = array_sum(array_column($trendData, 'variance_percentage'));
        return $sum / count($trendData);
    }
}
