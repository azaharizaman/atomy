<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use Nexus\CashManagement\ValueObjects\ForecastResultVO;
use Nexus\CashManagement\ValueObjects\ScenarioParametersVO;

/**
 * Cash Flow Forecast Interface
 */
interface CashFlowForecastInterface
{
    /**
     * Generate cash flow forecast
     */
    public function forecast(
        string $tenantId,
        ScenarioParametersVO $parameters,
        ?string $bankAccountId = null
    ): ForecastResultVO;

    /**
     * Generate multiple scenario forecasts
     *
     * @param array<ScenarioParametersVO> $scenarios
     * @return array<ForecastResultVO>
     */
    public function forecastScenarios(
        string $tenantId,
        array $scenarios,
        ?string $bankAccountId = null
    ): array;
}
