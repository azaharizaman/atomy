<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

/**
 * Forecast scenario type
 */
enum ForecastScenario: string
{
    case BEST = 'best';
    case EXPECTED = 'expected';
    case WORST = 'worst';
    case BASE = 'base';

    public function label(): string
    {
        return match ($this) {
            self::BEST => 'Best Case',
            self::EXPECTED => 'Expected Case',
            self::WORST => 'Worst Case',
            self::BASE => 'Base Case',
        };
    }

    public function multiplier(): float
    {
        return match ($this) {
            self::BEST => 1.1,
            self::EXPECTED => 1.0,
            self::WORST => 0.9,
            self::BASE => 1.0,
        };
    }
}
