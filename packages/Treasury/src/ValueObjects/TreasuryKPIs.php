<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use DateTimeImmutable;
use Nexus\Treasury\Contracts\TreasuryAnalyticsInterface;

final readonly class TreasuryKPIs implements TreasuryAnalyticsInterface
{
    public function __construct(
        public float $daysCashOnHand,
        public float $cashConversionCycle,
        public float $daysSalesOutstanding,
        public float $daysPayableOutstanding,
        public float $daysInventoryOutstanding,
        public float $quickRatio,
        public float $currentRatio,
        public float $workingCapitalRatio,
        public float $liquidityScore,
        public ?float $forecastAccuracy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            daysCashOnHand: (float) ($data['days_cash_on_hand'] ?? $data['daysCashOnHand'] ?? 0.0),
            cashConversionCycle: (float) ($data['cash_conversion_cycle'] ?? $data['cashConversionCycle'] ?? 0.0),
            daysSalesOutstanding: (float) ($data['days_sales_outstanding'] ?? $data['daysSalesOutstanding'] ?? 0.0),
            daysPayableOutstanding: (float) ($data['days_payable_outstanding'] ?? $data['daysPayableOutstanding'] ?? 0.0),
            daysInventoryOutstanding: (float) ($data['days_inventory_outstanding'] ?? $data['daysInventoryOutstanding'] ?? 0.0),
            quickRatio: (float) ($data['quick_ratio'] ?? $data['quickRatio'] ?? 0.0),
            currentRatio: (float) ($data['current_ratio'] ?? $data['currentRatio'] ?? 0.0),
            workingCapitalRatio: (float) ($data['working_capital_ratio'] ?? $data['workingCapitalRatio'] ?? 0.0),
            liquidityScore: (float) ($data['liquidity_score'] ?? $data['liquidityScore'] ?? 0.0),
            forecastAccuracy: isset($data['forecast_accuracy'])
                ? (float) $data['forecast_accuracy']
                : (isset($data['forecastAccuracy']) ? (float) $data['forecastAccuracy'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'daysCashOnHand' => $this->daysCashOnHand,
            'cashConversionCycle' => $this->cashConversionCycle,
            'daysSalesOutstanding' => $this->daysSalesOutstanding,
            'daysPayableOutstanding' => $this->daysPayableOutstanding,
            'daysInventoryOutstanding' => $this->daysInventoryOutstanding,
            'quickRatio' => $this->quickRatio,
            'currentRatio' => $this->currentRatio,
            'workingCapitalRatio' => $this->workingCapitalRatio,
            'liquidityScore' => $this->liquidityScore,
            'forecastAccuracy' => $this->forecastAccuracy,
        ];
    }

    public function hasNegativeCycle(): bool
    {
        return $this->cashConversionCycle < 0;
    }

    public function hasHealthyLiquidity(): bool
    {
        return $this->quickRatio >= 1.0 && $this->currentRatio >= 1.5;
    }

    public function getOverallHealthScore(): float
    {
        $scores = [
            min($this->daysCashOnHand / 30, 1.0) * 100,
            min($this->liquidityScore, 100),
            $this->hasHealthyLiquidity() ? 100 : 50,
            $this->forecastAccuracy ?? 0,
        ];

        return array_sum($scores) / count($scores);
    }

    public function getId(): string
    {
        return 'TRE-KPI-' . spl_object_id($this);
    }

    public function getTenantId(): string
    {
        return 'tenant';
    }

    public function getCalculationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public function getDaysCashOnHand(): float
    {
        return $this->daysCashOnHand;
    }

    public function getCashConversionCycle(): float
    {
        return $this->cashConversionCycle;
    }

    public function getDaysSalesOutstanding(): float
    {
        return $this->daysSalesOutstanding;
    }

    public function getDaysPayableOutstanding(): float
    {
        return $this->daysPayableOutstanding;
    }

    public function getDaysInventoryOutstanding(): float
    {
        return $this->daysInventoryOutstanding;
    }

    public function getQuickRatio(): float
    {
        return $this->quickRatio;
    }

    public function getCurrentRatio(): float
    {
        return $this->currentRatio;
    }

    public function getWorkingCapitalRatio(): float
    {
        return $this->workingCapitalRatio;
    }

    public function getLiquidityScore(): float
    {
        return $this->liquidityScore;
    }

    public function getForecastAccuracy(): ?float
    {
        return $this->forecastAccuracy;
    }

    public function getCurrency(): string
    {
        return 'USD';
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
