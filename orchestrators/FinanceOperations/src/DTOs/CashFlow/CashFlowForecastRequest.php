<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CashFlow;

/**
 * Request DTO for cash flow forecasting.
 *
 * Used to generate cash flow forecasts based on expected
 * receivables, payables, and other cash movements.
 *
 * @since 1.0.0
 */
final readonly class CashFlowForecastRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for the forecast
     * @param int $forecastDays Number of days to forecast (default: 30)
     * @param array<string, mixed> $scenarios Scenario parameters for Tier 2+ features
     * @param bool $includeReceivables Include expected receivables in forecast
     * @param bool $includePayables Include expected payables in forecast
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public int $forecastDays = 30,
        public array $scenarios = [],
        public bool $includeReceivables = true,
        public bool $includePayables = true,
    ) {}
}
