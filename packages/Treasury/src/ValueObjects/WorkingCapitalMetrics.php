<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

final readonly class WorkingCapitalMetrics
{
    public function __construct(
        public float $daysSalesOutstanding,
        public float $daysPayableOutstanding,
        public float $daysInventoryOutstanding,
        public float $cashConversionCycle,
        public float $workingCapital,
        public float $workingCapitalRatio,
        public array $optimizationOpportunities = [],
        public array $recommendations = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            daysSalesOutstanding: (float) ($data['days_sales_outstanding'] ?? $data['daysSalesOutstanding'] ?? 0.0),
            daysPayableOutstanding: (float) ($data['days_payable_outstanding'] ?? $data['daysPayableOutstanding'] ?? 0.0),
            daysInventoryOutstanding: (float) ($data['days_inventory_outstanding'] ?? $data['daysInventoryOutstanding'] ?? 0.0),
            cashConversionCycle: (float) ($data['cash_conversion_cycle'] ?? $data['cashConversionCycle'] ?? 0.0),
            workingCapital: (float) ($data['working_capital'] ?? $data['workingCapital'] ?? 0.0),
            workingCapitalRatio: (float) ($data['working_capital_ratio'] ?? $data['workingCapitalRatio'] ?? 0.0),
            optimizationOpportunities: $data['optimization_opportunities'] ?? $data['optimizationOpportunities'] ?? [],
            recommendations: $data['recommendations'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'daysSalesOutstanding' => $this->daysSalesOutstanding,
            'daysPayableOutstanding' => $this->daysPayableOutstanding,
            'daysInventoryOutstanding' => $this->daysInventoryOutstanding,
            'cashConversionCycle' => $this->cashConversionCycle,
            'workingCapital' => $this->workingCapital,
            'workingCapitalRatio' => $this->workingCapitalRatio,
            'optimizationOpportunities' => $this->optimizationOpportunities,
            'recommendations' => $this->recommendations,
        ];
    }

    public function hasNegativeCycle(): bool
    {
        return $this->cashConversionCycle < 0;
    }

    public function isHealthy(): bool
    {
        return $this->workingCapitalRatio >= 1.0 && $this->cashConversionCycle < 90;
    }

    public function getDsoStatus(): string
    {
        return match (true) {
            $this->daysSalesOutstanding <= 30 => 'excellent',
            $this->daysSalesOutstanding <= 45 => 'good',
            $this->daysSalesOutstanding <= 60 => 'fair',
            default => 'needs_improvement',
        };
    }

    public function getDpoStatus(): string
    {
        return match (true) {
            $this->daysPayableOutstanding >= 60 => 'excellent',
            $this->daysPayableOutstanding >= 45 => 'good',
            $this->daysPayableOutstanding >= 30 => 'fair',
            default => 'needs_improvement',
        };
    }
}
