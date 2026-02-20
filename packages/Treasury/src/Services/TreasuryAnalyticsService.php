<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\Integration\InventoryDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\ReceivableDataProviderInterface;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Contracts\TreasuryAnalyticsInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\ValueObjects\TreasuryKPIs;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class TreasuryAnalyticsService
{
    public function __construct(
        private TreasuryPolicyQueryInterface $policyQuery,
        private InvestmentQueryInterface $investmentQuery,
        private TreasuryPositionService $positionService,
        private ?PayableDataProviderInterface $payableProvider = null,
        private ?ReceivableDataProviderInterface $receivableProvider = null,
        private ?InventoryDataProviderInterface $inventoryProvider = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function calculateKPIs(
        string $tenantId,
        ?DateTimeImmutable $asOfDate = null
    ): TreasuryAnalyticsInterface {
        $date = $asOfDate ?? new DateTimeImmutable();

        $daysCashOnHand = $this->positionService->getDaysCashOnHand($tenantId, null, $date);
        $dso = $this->getDaysSalesOutstanding($tenantId);
        $dpo = $this->getDaysPayableOutstanding($tenantId);
        $dio = $this->getDaysInventoryOutstanding($tenantId);
        $cashConversionCycle = $dso + $dio - $dpo;

        $quickRatio = $this->calculateQuickRatio($tenantId);
        $currentRatio = $this->calculateCurrentRatio($tenantId);
        $workingCapitalRatio = $this->calculateWorkingCapitalRatio($tenantId);

        $liquidityScore = $this->calculateLiquidityScore(
            $daysCashOnHand,
            $quickRatio,
            $currentRatio
        );

        $forecastAccuracy = $this->getForecastAccuracy($tenantId);

        $position = $this->positionService->calculatePosition($tenantId, null, $date);
        $currency = $position->getCurrency();

        return new TreasuryKPIs(
            id: TreasuryKPIs::generateId(),
            tenantId: $tenantId,
            calculatedAt: $date,
            currency: $currency,
            daysCashOnHand: $daysCashOnHand,
            cashConversionCycle: $cashConversionCycle,
            daysSalesOutstanding: $dso,
            daysPayableOutstanding: $dpo,
            daysInventoryOutstanding: $dio,
            quickRatio: $quickRatio,
            currentRatio: $currentRatio,
            workingCapitalRatio: $workingCapitalRatio,
            liquidityScore: $liquidityScore,
            forecastAccuracy: $forecastAccuracy
        );
    }

    public function getLiquidityScore(string $tenantId, ?DateTimeImmutable $asOfDate = null): float
    {
        $date = $asOfDate ?? new DateTimeImmutable();

        $daysCashOnHand = $this->positionService->getDaysCashOnHand($tenantId, null, $date);
        $quickRatio = $this->calculateQuickRatio($tenantId);
        $currentRatio = $this->calculateCurrentRatio($tenantId);

        return $this->calculateLiquidityScore($daysCashOnHand, $quickRatio, $currentRatio);
    }

    public function getCashFlowMetrics(string $tenantId, ?DateTimeImmutable $asOfDate = null): array
    {
        $date = $asOfDate ?? new DateTimeImmutable();
        $position = $this->positionService->calculatePosition($tenantId, null, $date);

        return [
            'total_cash_balance' => $position->getTotalCashBalance()->getAmount(),
            'available_cash' => $position->getAvailableCashBalance()->getAmount(),
            'invested_cash' => $position->getInvestedCashBalance()->getAmount(),
            'projected_inflows' => $position->getProjectedInflows()->getAmount(),
            'projected_outflows' => $position->getProjectedOutflows()->getAmount(),
            'net_cash_flow' => $position->getNetCashFlow()->getAmount(),
            'days_cash_on_hand' => $this->positionService->getDaysCashOnHand($tenantId, null, $date),
            'currency' => $position->getCurrency(),
        ];
    }

    public function getInvestmentMetrics(string $tenantId): array
    {
        $activeInvestments = $this->investmentQuery->findActiveByTenantId($tenantId);
        $totalInvested = $this->investmentQuery->sumPrincipalByTenantId($tenantId);
        $activeCount = $this->investmentQuery->countActiveByTenantId($tenantId);

        $avgInterestRate = 0.0;
        $totalAccruedInterest = 0.0;

        foreach ($activeInvestments as $investment) {
            $avgInterestRate += $investment->getInterestRate();
            $totalAccruedInterest += $investment->getAccruedInterest()->getAmount();
        }

        if ($activeCount > 0) {
            $avgInterestRate /= $activeCount;
        }

        return [
            'total_invested' => $totalInvested,
            'active_investments' => $activeCount,
            'average_interest_rate' => $avgInterestRate,
            'total_accrued_interest' => $totalAccruedInterest,
            'matured_investments' => count($this->investmentQuery->findMaturedByTenantId($tenantId)),
        ];
    }

    public function getWorkingCapitalMetrics(string $tenantId): array
    {
        $dso = $this->getDaysSalesOutstanding($tenantId);
        $dpo = $this->getDaysPayableOutstanding($tenantId);
        $dio = 0.0;
        $ccc = $dso + $dio - $dpo;

        return [
            'days_sales_outstanding' => $dso,
            'days_payable_outstanding' => $dpo,
            'days_inventory_outstanding' => $dio,
            'cash_conversion_cycle' => $ccc,
            'working_capital_ratio' => $this->calculateCurrentRatio($tenantId),
        ];
    }

    public function compareToBenchmarks(string $tenantId, array $benchmarks, ?DateTimeImmutable $asOfDate = null): array
    {
        $kpis = $this->calculateKPIs($tenantId, $asOfDate);
        $comparisons = [];

        foreach ($benchmarks as $metric => $benchmark) {
            $actual = match ($metric) {
                'days_cash_on_hand' => $kpis->daysCashOnHand,
                'cash_conversion_cycle' => $kpis->cashConversionCycle,
                'quick_ratio' => $kpis->quickRatio,
                'current_ratio' => $kpis->currentRatio,
                'liquidity_score' => $kpis->liquidityScore,
                default => null,
            };

            if ($actual !== null) {
                $comparisons[$metric] = [
                    'actual' => $actual,
                    'benchmark' => $benchmark,
                    'variance' => $actual - $benchmark,
                    'status' => $actual >= $benchmark ? 'met' : 'below',
                ];
            }
        }

        return $comparisons;
    }

    private function getDaysSalesOutstanding(string $tenantId): float
    {
        if ($this->receivableProvider === null) {
            return 0.0;
        }

        return $this->receivableProvider->getDaysSalesOutstanding($tenantId);
    }

    private function getDaysPayableOutstanding(string $tenantId): float
    {
        if ($this->payableProvider === null) {
            return 0.0;
        }

        return $this->payableProvider->getDaysPayableOutstanding($tenantId);
    }

    private function getDaysInventoryOutstanding(string $tenantId): float
    {
        if ($this->inventoryProvider === null) {
            return 0.0;
        }

        return $this->inventoryProvider->getDaysInventoryOutstanding($tenantId);
    }

    private function calculateQuickRatio(string $tenantId): float
    {
        if ($this->receivableProvider === null || $this->payableProvider === null) {
            return -1.0;
        }

        $date = date('Y-m-d');
        $receivables = $this->receivableProvider->getTotalReceivables($tenantId, $date);
        $currentLiabilities = $this->payableProvider->getTotalPayables($tenantId, $date);

        if ($currentLiabilities <= 0) {
            return -1.0;
        }

        return $receivables / $currentLiabilities;
    }

    private function calculateCurrentRatio(string $tenantId): float
    {
        if ($this->receivableProvider === null || $this->payableProvider === null) {
            return -1.0;
        }

        $date = date('Y-m-d');
        $receivables = $this->receivableProvider->getTotalReceivables($tenantId, $date);
        
        $inventory = 0.0;
        if ($this->inventoryProvider !== null) {
            $inventory = $this->inventoryProvider->getTotalInventoryValue($tenantId, $date);
        }

        $currentAssets = $receivables + $inventory;
        $currentLiabilities = $this->payableProvider->getTotalPayables($tenantId, $date);

        if ($currentLiabilities <= 0) {
            return -1.0;
        }

        return $currentAssets / $currentLiabilities;
    }

    private function calculateWorkingCapitalRatio(string $tenantId): float
    {
        if ($this->receivableProvider === null || $this->payableProvider === null) {
            return -1.0;
        }

        $date = date('Y-m-d');
        $receivables = $this->receivableProvider->getTotalReceivables($tenantId, $date);
        
        $inventory = 0.0;
        if ($this->inventoryProvider !== null) {
            $inventory = $this->inventoryProvider->getTotalInventoryValue($tenantId, $date);
        }

        $currentAssets = $receivables + $inventory;
        $currentLiabilities = $this->payableProvider->getTotalPayables($tenantId, $date);

        $workingCapital = $currentAssets - $currentLiabilities;
        
        $revenue = $receivables * 4;
        if ($revenue <= 0) {
            return -1.0;
        }

        return $workingCapital / $revenue;
    }

    private function calculateLiquidityScore(
        float $daysCashOnHand,
        float $quickRatio,
        float $currentRatio
    ): float {
        $cashScore = min($daysCashOnHand / 30, 1.0) * 40;
        $quickScore = min($quickRatio / 1.0, 1.0) * 30;
        $currentScore = min($currentRatio / 2.0, 1.0) * 30;

        return $cashScore + $quickScore + $currentScore;
    }

    private function getForecastAccuracy(string $tenantId): ?float
    {
        return null;
    }
}
