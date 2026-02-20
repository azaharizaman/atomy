<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Treasury\Enums\ForecastScenario;
use Nexus\Treasury\Exceptions\InvalidForecastScenarioException;
use PHPUnit\Framework\TestCase;

final class InvalidForecastScenarioExceptionTest extends TestCase
{
    public function test_for_scenario_creates_exception(): void
    {
        $exception = InvalidForecastScenarioException::forScenario('invalid_scenario');

        $this->assertStringContainsString('invalid_scenario', $exception->getMessage());
    }

    public function test_cannot_mix_scenarios_creates_exception(): void
    {
        $exception = InvalidForecastScenarioException::cannotMixScenarios(
            ForecastScenario::OPTIMISTIC,
            ForecastScenario::PESSIMISTIC
        );

        $this->assertStringContainsString('optimistic', $exception->getMessage());
        $this->assertStringContainsString('pessimistic', $exception->getMessage());
    }

    public function test_insufficient_data_creates_exception(): void
    {
        $exception = InvalidForecastScenarioException::insufficientData('stress');

        $this->assertStringContainsString('stress', $exception->getMessage());
    }

    public function test_invalid_date_range_creates_exception(): void
    {
        $exception = InvalidForecastScenarioException::invalidDateRange('End date before start date');

        $this->assertStringContainsString('End date before start date', $exception->getMessage());
    }
}
