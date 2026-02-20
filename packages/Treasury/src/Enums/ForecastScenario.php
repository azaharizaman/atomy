<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

enum ForecastScenario: string
{
    case OPTIMISTIC = 'optimistic';
    case BASE = 'base';
    case PESSIMISTIC = 'pessimistic';

    public function label(): string
    {
        return match ($this) {
            self::OPTIMISTIC => 'Optimistic',
            self::BASE => 'Base',
            self::PESSIMISTIC => 'Pessimistic',
        };
    }

    public function isOptimistic(): bool
    {
        return $this === self::OPTIMISTIC;
    }

    public function isBase(): bool
    {
        return $this === self::BASE;
    }

    public function isPessimistic(): bool
    {
        return $this === self::PESSIMISTIC;
    }

    public function riskFactor(): float
    {
        return match ($this) {
            self::OPTIMISTIC => 0.8,
            self::BASE => 1.0,
            self::PESSIMISTIC => 1.2,
        };
    }

    public function adjustmentPercentage(): float
    {
        return match ($this) {
            self::OPTIMISTIC => 0.1,
            self::BASE => 0.0,
            self::PESSIMISTIC => -0.15,
        };
    }
}
