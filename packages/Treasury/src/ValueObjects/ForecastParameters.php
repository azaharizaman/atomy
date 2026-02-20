<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use Nexus\Treasury\Enums\ForecastScenario;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ForecastParameters
{
    public function __construct(
        public ForecastScenario $scenario,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public float $growthRateAdjustment = 0.0,
        public float $collectionRateAdjustment = 0.0,
        public float $paymentRateAdjustment = 0.0,
        public array $customAssumptions = [],
    ) {
        if ($endDate <= $startDate) {
            throw new InvalidArgumentException('End date must be after start date');
        }
    }

    public static function fromArray(array $data): self
    {
        $scenario = match (strtolower($data['scenario'] ?? 'base')) {
            'optimistic' => ForecastScenario::OPTIMISTIC,
            'pessimistic' => ForecastScenario::PESSIMISTIC,
            default => ForecastScenario::BASE,
        };

        return new self(
            scenario: $scenario,
            startDate: new DateTimeImmutable($data['start_date'] ?? $data['startDate']),
            endDate: new DateTimeImmutable($data['end_date'] ?? $data['endDate']),
            growthRateAdjustment: (float) ($data['growth_rate_adjustment'] ?? $data['growthRateAdjustment'] ?? 0.0),
            collectionRateAdjustment: (float) ($data['collection_rate_adjustment'] ?? $data['collectionRateAdjustment'] ?? 0.0),
            paymentRateAdjustment: (float) ($data['payment_rate_adjustment'] ?? $data['paymentRateAdjustment'] ?? 0.0),
            customAssumptions: $data['custom_assumptions'] ?? $data['customAssumptions'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'scenario' => $this->scenario->value,
            'startDate' => $this->startDate->format('Y-m-d'),
            'endDate' => $this->endDate->format('Y-m-d'),
            'growthRateAdjustment' => $this->growthRateAdjustment,
            'collectionRateAdjustment' => $this->collectionRateAdjustment,
            'paymentRateAdjustment' => $this->paymentRateAdjustment,
            'customAssumptions' => $this->customAssumptions,
        ];
    }

    public function getDurationDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days;
    }

    public function getEffectiveGrowthRate(): float
    {
        return 1.0 + $this->growthRateAdjustment + $this->scenario->adjustmentPercentage();
    }
}
