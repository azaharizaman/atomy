<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CashFlow;

/**
 * Result DTO for cash flow forecasting.
 *
 * Contains forecasted cash flow data including projected
 * inflows, outflows, and net cash position.
 *
 * @since 1.0.0
 */
final readonly class CashFlowForecastResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string $tenantId Tenant identifier
     * @param array<int, array{date: string, inflow: string, outflow: string, net: string, balance: string}> $forecastData Daily forecast data
     * @param array<int, array{source: string, amount: string, expectedDate: string}> $inflows Projected cash inflows
     * @param array<int, array{destination: string, amount: string, dueDate: string}> $outflows Projected cash outflows
     * @param string $netCashFlow Net cash flow for the forecast period
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $tenantId,
        public array $forecastData = [],
        public array $inflows = [],
        public array $outflows = [],
        public string $netCashFlow = '0',
        public ?string $error = null,
    ) {}
}
