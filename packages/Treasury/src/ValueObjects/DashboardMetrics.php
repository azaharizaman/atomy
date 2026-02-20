<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use DateTimeImmutable;
use Nexus\Treasury\Contracts\TreasuryDashboardInterface;

final readonly class DashboardMetrics implements TreasuryDashboardInterface
{
    public function __construct(
        public Money $totalCashPosition,
        public Money $availableCashBalance,
        public Money $investedCashBalance,
        public Money $reservedCashBalance,
        public Money $projectedCashFlowToday,
        public Money $projectedCashFlowWeek,
        public Money $projectedCashFlowMonth,
        public float $daysCashOnHand,
        public float $cashConversionCycle,
        public int $pendingApprovalsCount,
        public int $activeInvestmentsCount,
        public int $activeIntercompanyLoansCount,
        public array $alerts = [],
        public array $kpiSummary = [],
        public DateTimeImmutable $calculatedAt = new DateTimeImmutable(),
    ) {}

    public static function fromArray(array $data): self
    {
        $currency = $data['currency'] ?? 'USD';

        return new self(
            totalCashPosition: Money::of($data['total_cash_position'] ?? $data['totalCashPosition'] ?? 0, $currency),
            availableCashBalance: Money::of($data['available_cash_balance'] ?? $data['availableCashBalance'] ?? 0, $currency),
            investedCashBalance: Money::of($data['invested_cash_balance'] ?? $data['investedCashBalance'] ?? 0, $currency),
            reservedCashBalance: Money::of($data['reserved_cash_balance'] ?? $data['reservedCashBalance'] ?? 0, $currency),
            projectedCashFlowToday: Money::of($data['projected_cash_flow_today'] ?? $data['projectedCashFlowToday'] ?? 0, $currency),
            projectedCashFlowWeek: Money::of($data['projected_cash_flow_week'] ?? $data['projectedCashFlowWeek'] ?? 0, $currency),
            projectedCashFlowMonth: Money::of($data['projected_cash_flow_month'] ?? $data['projectedCashFlowMonth'] ?? 0, $currency),
            daysCashOnHand: (float) ($data['days_cash_on_hand'] ?? $data['daysCashOnHand'] ?? 0.0),
            cashConversionCycle: (float) ($data['cash_conversion_cycle'] ?? $data['cashConversionCycle'] ?? 0.0),
            pendingApprovalsCount: (int) ($data['pending_approvals_count'] ?? $data['pendingApprovalsCount'] ?? 0),
            activeInvestmentsCount: (int) ($data['active_investments_count'] ?? $data['activeInvestmentsCount'] ?? 0),
            activeIntercompanyLoansCount: (int) ($data['active_intercompany_loans_count'] ?? $data['activeIntercompanyLoansCount'] ?? 0),
            alerts: $data['alerts'] ?? [],
            kpiSummary: $data['kpi_summary'] ?? $data['kpiSummary'] ?? [],
            calculatedAt: new DateTimeImmutable($data['calculated_at'] ?? $data['calculatedAt'] ?? 'now'),
        );
    }

    public function toArray(): array
    {
        return [
            'totalCashPosition' => $this->totalCashPosition->toArray(),
            'availableCashBalance' => $this->availableCashBalance->toArray(),
            'investedCashBalance' => $this->investedCashBalance->toArray(),
            'reservedCashBalance' => $this->reservedCashBalance->toArray(),
            'projectedCashFlowToday' => $this->projectedCashFlowToday->toArray(),
            'projectedCashFlowWeek' => $this->projectedCashFlowWeek->toArray(),
            'projectedCashFlowMonth' => $this->projectedCashFlowMonth->toArray(),
            'daysCashOnHand' => $this->daysCashOnHand,
            'cashConversionCycle' => $this->cashConversionCycle,
            'pendingApprovalsCount' => $this->pendingApprovalsCount,
            'activeInvestmentsCount' => $this->activeInvestmentsCount,
            'activeIntercompanyLoansCount' => $this->activeIntercompanyLoansCount,
            'alerts' => $this->alerts,
            'kpiSummary' => $this->kpiSummary,
            'calculatedAt' => $this->calculatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getCurrency(): string
    {
        return $this->totalCashPosition->getCurrency();
    }

    public function hasAlerts(): bool
    {
        return !empty($this->alerts);
    }

    public function getCriticalAlerts(): array
    {
        return array_filter($this->alerts, fn($alert) => ($alert['severity'] ?? 'info') === 'critical');
    }

    public function getId(): string
    {
        return 'TRE-DASH-' . spl_object_id($this);
    }

    public function getTenantId(): string
    {
        return 'tenant';
    }

    public function getDashboardDate(): DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    public function getAvailableCashBalance(): Money
    {
        return $this->availableCashBalance;
    }

    public function getTotalCashPosition(): Money
    {
        return $this->totalCashPosition;
    }

    public function getInvestedCashBalance(): Money
    {
        return $this->investedCashBalance;
    }

    public function getReservedCashBalance(): Money
    {
        return $this->reservedCashBalance;
    }

    public function getProjectedCashFlowToday(): Money
    {
        return $this->projectedCashFlowToday;
    }

    public function getProjectedCashFlowWeek(): Money
    {
        return $this->projectedCashFlowWeek;
    }

    public function getProjectedCashFlowMonth(): Money
    {
        return $this->projectedCashFlowMonth;
    }

    public function getDaysCashOnHand(): float
    {
        return $this->daysCashOnHand;
    }

    public function getCashConversionCycle(): float
    {
        return $this->cashConversionCycle;
    }

    public function getPendingApprovalsCount(): int
    {
        return $this->pendingApprovalsCount;
    }

    public function getActiveInvestmentsCount(): int
    {
        return $this->activeInvestmentsCount;
    }

    public function getActiveIntercompanyLoansCount(): int
    {
        return $this->activeIntercompanyLoansCount;
    }

    public function getAlerts(): array
    {
        return $this->alerts;
    }

    public function getKpiSummary(): array
    {
        return $this->kpiSummary;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->calculatedAt;
    }
}
