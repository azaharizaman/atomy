<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Enums;

/**
 * Forecast Scenario Type Enumeration
 *
 * Defines the types of cash flow forecast scenarios.
 */
enum ForecastScenarioType: string
{
    case OPTIMISTIC = 'optimistic';
    case BASELINE = 'baseline';
    case PESSIMISTIC = 'pessimistic';
    case CUSTOM = 'custom';

    /**
     * Get default collection probability for scenario
     */
    public function defaultCollectionProbability(): float
    {
        return match ($this) {
            self::OPTIMISTIC => 0.95,
            self::BASELINE => 0.85,
            self::PESSIMISTIC => 0.70,
            self::CUSTOM => 0.85,
        };
    }

    /**
     * Get default payment delay (in days) for scenario
     */
    public function defaultPaymentDelay(): int
    {
        return match ($this) {
            self::OPTIMISTIC => 0,
            self::BASELINE => 3,
            self::PESSIMISTIC => 7,
            self::CUSTOM => 3,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::OPTIMISTIC => 'Optimistic',
            self::BASELINE => 'Baseline',
            self::PESSIMISTIC => 'Pessimistic',
            self::CUSTOM => 'Custom',
        };
    }
}
