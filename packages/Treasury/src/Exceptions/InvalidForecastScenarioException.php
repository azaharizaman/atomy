<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

use Nexus\Treasury\Enums\ForecastScenario;

final class InvalidForecastScenarioException extends TreasuryException
{
    public static function forScenario(string $scenario): self
    {
        return new self("Invalid forecast scenario: {$scenario}");
    }

    public static function cannotMixScenarios(ForecastScenario $scenario1, ForecastScenario $scenario2): self
    {
        return new self(
            "Cannot mix forecast scenarios: {$scenario1->value} and {$scenario2->value}"
        );
    }

    public static function insufficientData(string $scenario): self
    {
        return new self("Insufficient historical data for {$scenario} forecast scenario");
    }

    public static function invalidDateRange(string $reason): self
    {
        return new self("Invalid forecast date range: {$reason}");
    }
}
