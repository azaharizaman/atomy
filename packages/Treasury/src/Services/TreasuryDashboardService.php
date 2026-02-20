<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\Integration\InventoryDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\ReceivableDataProviderInterface;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Contracts\IntercompanyLoanQueryInterface;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalQueryInterface;
use Nexus\Treasury\Contracts\TreasuryDashboardInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\ValueObjects\DashboardMetrics;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class TreasuryDashboardService
{
    public function __construct(
        private LiquidityPoolQueryInterface $liquidityPoolQuery,
        private InvestmentQueryInterface $investmentQuery,
        private IntercompanyLoanQueryInterface $loanQuery,
        private TreasuryApprovalQueryInterface $approvalQuery,
        private TreasuryPolicyQueryInterface $policyQuery,
        private TreasuryPositionService $positionService,
        private ?PayableDataProviderInterface $payableProvider = null,
        private ?ReceivableDataProviderInterface $receivableProvider = null,
        private ?InventoryDataProviderInterface $inventoryProvider = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function getDashboard(
        string $tenantId,
        ?DateTimeImmutable $asOfDate = null
    ): TreasuryDashboardInterface {
        $date = $asOfDate ?? new DateTimeImmutable();
        $currency = $this->getDefaultCurrency($tenantId);

        $position = $this->positionService->calculatePosition($tenantId, null, $date);

        $projectedToday = $this->calculateProjectedCashFlow($tenantId, $currency, 1, $date);
        $projectedWeek = $this->calculateProjectedCashFlow($tenantId, $currency, 7, $date);
        $projectedMonth = $this->calculateProjectedCashFlow($tenantId, $currency, 30, $date);

        $daysCashOnHand = $this->positionService->getDaysCashOnHand($tenantId, null, $date);
        $cashConversionCycle = $this->calculateCashConversionCycle($tenantId);

        $pendingApprovalsCount = $this->approvalQuery->countPendingByTenantId($tenantId);
        $activeInvestmentsCount = $this->investmentQuery->countActiveByTenantId($tenantId);
        $activeIntercompanyLoansCount = $this->loanQuery->countActiveByTenantId($tenantId);

        $alerts = $this->generateAlerts(
            $position->getAvailableCashBalance(),
            $daysCashOnHand,
            $cashConversionCycle,
            $pendingApprovalsCount
        );

        $kpiSummary = $this->buildKpiSummary(
            $daysCashOnHand,
            $cashConversionCycle,
            $position->getAvailableCashBalance()
        );

        return new DashboardMetrics(
            id: DashboardMetrics::generateId(),
            tenantId: $tenantId,
            totalCashPosition: $position->getTotalCashBalance(),
            availableCashBalance: $position->getAvailableCashBalance(),
            investedCashBalance: $position->getInvestedCashBalance(),
            reservedCashBalance: $position->getReservedCashBalance(),
            projectedCashFlowToday: $projectedToday,
            projectedCashFlowWeek: $projectedWeek,
            projectedCashFlowMonth: $projectedMonth,
            daysCashOnHand: $daysCashOnHand,
            cashConversionCycle: $cashConversionCycle,
            pendingApprovalsCount: $pendingApprovalsCount,
            activeInvestmentsCount: $activeInvestmentsCount,
            activeIntercompanyLoansCount: $activeIntercompanyLoansCount,
            alerts: $alerts,
            kpiSummary: $kpiSummary,
            calculatedAt: $date
        );
    }

    public function getAlerts(string $tenantId, ?DateTimeImmutable $asOfDate = null): array
    {
        $date = $asOfDate ?? new DateTimeImmutable();
        $position = $this->positionService->calculatePosition($tenantId, null, $date);
        $daysCashOnHand = $this->positionService->getDaysCashOnHand($tenantId);
        $cashConversionCycle = $this->calculateCashConversionCycle($tenantId);
        $pendingApprovalsCount = $this->approvalQuery->countPendingByTenantId($tenantId);

        return $this->generateAlerts(
            $position->getAvailableCashBalance(),
            $daysCashOnHand,
            $cashConversionCycle,
            $pendingApprovalsCount
        );
    }

    public function getKpiSummary(string $tenantId, ?DateTimeImmutable $asOfDate = null): array
    {
        $date = $asOfDate ?? new DateTimeImmutable();
        $position = $this->positionService->calculatePosition($tenantId, null, $date);
        $daysCashOnHand = $this->positionService->getDaysCashOnHand($tenantId, null, $date);
        $cashConversionCycle = $this->calculateCashConversionCycle($tenantId);

        return $this->buildKpiSummary(
            $daysCashOnHand,
            $cashConversionCycle,
            $position->getAvailableCashBalance()
        );
    }

    public function getCashPositionSummary(string $tenantId, ?DateTimeImmutable $asOfDate = null): array
    {
        $date = $asOfDate ?? new DateTimeImmutable();
        $position = $this->positionService->calculatePosition($tenantId, null, $date);
        $minimumCheck = $this->positionService->compareToMinimumBalance($tenantId);

        return [
            'total_cash' => $position->getTotalCashBalance()->toArray(),
            'available_cash' => $position->getAvailableCashBalance()->toArray(),
            'invested_cash' => $position->getInvestedCashBalance()->toArray(),
            'reserved_cash' => $position->getReservedCashBalance()->toArray(),
            'net_position' => $position->getNetPosition()->toArray(),
            'projected_inflows' => $position->getProjectedInflows()->toArray(),
            'projected_outflows' => $position->getProjectedOutflows()->toArray(),
            'minimum_balance_status' => $minimumCheck,
            'as_of_date' => $date->format('Y-m-d'),
        ];
    }

    private function calculateProjectedCashFlow(string $tenantId, string $currency, int $days, DateTimeImmutable $asOfDate): Money
    {
        $totalInflows = 0.0;
        $totalOutflows = 0.0;

        if ($this->receivableProvider !== null) {
            $totalReceivables = $this->receivableProvider->getTotalReceivables(
                $tenantId,
                $asOfDate->format('Y-m-d')
            );
            $collectionPeriod = $this->receivableProvider->getAverageCollectionPeriod($tenantId);
            if ($collectionPeriod > 0) {
                $totalInflows = ($totalReceivables / $collectionPeriod) * $days;
            }
        }

        if ($this->payableProvider !== null) {
            $totalPayables = $this->payableProvider->getTotalPayables(
                $tenantId,
                $asOfDate->format('Y-m-d')
            );
            $paymentPeriod = $this->payableProvider->getAveragePaymentPeriod($tenantId);
            if ($paymentPeriod > 0) {
                $totalOutflows = ($totalPayables / $paymentPeriod) * $days;
            }
        }

        return Money::of($totalInflows - $totalOutflows, $currency);
    }

    private function calculateCashConversionCycle(string $tenantId): float
    {
        $dso = 0.0;
        $dpo = 0.0;
        $dio = 0.0;

        if ($this->receivableProvider !== null) {
            $dso = $this->receivableProvider->getDaysSalesOutstanding($tenantId);
        }

        if ($this->payableProvider !== null) {
            $dpo = $this->payableProvider->getDaysPayableOutstanding($tenantId);
        }

        if ($this->inventoryProvider !== null) {
            $dio = $this->inventoryProvider->getDaysInventoryOutstanding($tenantId);
        }

        return $dso + $dio - $dpo;
    }

    private function generateAlerts(
        Money $availableCash,
        float $daysCashOnHand,
        float $cashConversionCycle,
        int $pendingApprovals
    ): array {
        $alerts = [];

        if ($daysCashOnHand < 7) {
            $alerts[] = [
                'type' => 'low_cash',
                'severity' => 'critical',
                'message' => sprintf('Days cash on hand is critically low: %.1f days', $daysCashOnHand),
                'value' => $daysCashOnHand,
                'threshold' => 7,
            ];
        } elseif ($daysCashOnHand < 14) {
            $alerts[] = [
                'type' => 'low_cash',
                'severity' => 'warning',
                'message' => sprintf('Days cash on hand is below recommended: %.1f days', $daysCashOnHand),
                'value' => $daysCashOnHand,
                'threshold' => 14,
            ];
        }

        if ($cashConversionCycle > 60) {
            $alerts[] = [
                'type' => 'cash_cycle',
                'severity' => 'warning',
                'message' => sprintf('Cash conversion cycle is long: %.1f days', $cashConversionCycle),
                'value' => $cashConversionCycle,
                'threshold' => 60,
            ];
        }

        if ($pendingApprovals > 10) {
            $alerts[] = [
                'type' => 'approvals',
                'severity' => 'warning',
                'message' => sprintf('%d pending approvals require attention', $pendingApprovals),
                'value' => $pendingApprovals,
                'threshold' => 10,
            ];
        }

        return $alerts;
    }

    private function buildKpiSummary(
        float $daysCashOnHand,
        float $cashConversionCycle,
        Money $availableCash
    ): array {
        return [
            'days_cash_on_hand' => [
                'value' => $daysCashOnHand,
                'status' => $daysCashOnHand >= 30 ? 'healthy' : ($daysCashOnHand >= 14 ? 'warning' : 'critical'),
                'unit' => 'days',
            ],
            'cash_conversion_cycle' => [
                'value' => $cashConversionCycle,
                'status' => $cashConversionCycle <= 30 ? 'healthy' : ($cashConversionCycle <= 60 ? 'warning' : 'critical'),
                'unit' => 'days',
            ],
            'available_cash' => [
                'value' => $availableCash->getAmount(),
                'formatted' => $availableCash->format(),
                'currency' => $availableCash->getCurrency(),
            ],
        ];
    }

    private function getDefaultCurrency(string $tenantId): string
    {
        $policy = $this->policyQuery->findEffectiveForDate(
            $tenantId,
            new DateTimeImmutable()
        );

        if ($policy !== null) {
            return $policy->getMinimumCashBalance()->getCurrency();
        }

        return $_ENV['DEFAULT_CURRENCY'] ?? 'USD';
    }
}
