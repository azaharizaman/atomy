<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

use Nexus\CashManagement\Enums\ForecastScenarioType;
use InvalidArgumentException;

/**
 * Scenario Parameters Value Object
 *
 * Immutable parameters for cash flow forecast scenarios.
 */
final readonly class ScenarioParametersVO
{
    public function __construct(
        private ForecastScenarioType $scenarioType,
        private float $collectionProbability,
        private int $paymentDelayDays,
        private int $forecastHorizonDays = 90
    ) {
        $this->validate();
    }

    /**
     * Validate scenario parameters
     */
    private function validate(): void
    {
        if ($this->collectionProbability < 0 || $this->collectionProbability > 1) {
            throw new InvalidArgumentException('Collection probability must be between 0 and 1');
        }

        if ($this->paymentDelayDays < 0) {
            throw new InvalidArgumentException('Payment delay cannot be negative');
        }

        if ($this->forecastHorizonDays < 1 || $this->forecastHorizonDays > 365) {
            throw new InvalidArgumentException('Forecast horizon must be between 1 and 365 days');
        }
    }

    public function getScenarioType(): ForecastScenarioType
    {
        return $this->scenarioType;
    }

    public function getCollectionProbability(): float
    {
        return $this->collectionProbability;
    }

    public function getPaymentDelayDays(): int
    {
        return $this->paymentDelayDays;
    }

    public function getForecastHorizonDays(): int
    {
        return $this->forecastHorizonDays;
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'scenario_type' => $this->scenarioType->value,
            'collection_probability' => $this->collectionProbability,
            'payment_delay_days' => $this->paymentDelayDays,
            'forecast_horizon_days' => $this->forecastHorizonDays,
        ];
    }

    /**
     * Create from scenario type with default parameters
     */
    public static function fromScenarioType(ForecastScenarioType $type, int $horizonDays = 90): self
    {
        return new self(
            scenarioType: $type,
            collectionProbability: $type->defaultCollectionProbability(),
            paymentDelayDays: $type->defaultPaymentDelay(),
            forecastHorizonDays: $horizonDays
        );
    }
}
