<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DataProviders;

use Nexus\AccountVarianceAnalysis\Contracts\VarianceDataProviderInterface;
use Nexus\AccountVarianceAnalysis\ValueObjects\AccountVariance;

/**
 * Data provider that integrates with Nexus\Budget for variance data.
 */
final readonly class BudgetDataProvider implements VarianceDataProviderInterface
{
    public function __construct(
        // Injected dependencies from consuming application
    ) {}

    /**
     * @return array<string, float>
     */
    public function getActualAmounts(string $tenantId, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, float>
     */
    public function getBudgetAmounts(string $tenantId, string $periodId, string $budgetId): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, float>
     */
    public function getPriorPeriodAmounts(string $tenantId, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, array<string, float>>
     */
    public function getHistoricalData(string $tenantId, int $numberOfPeriods): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, float>
     */
    public function getForecastAmounts(string $tenantId, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }
}
