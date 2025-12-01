<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\DTOs\VarianceReportRequest;
use Nexus\AccountingOperations\DataProviders\BudgetDataProvider;
use Nexus\AccountVarianceAnalysis\Services\VarianceCalculator;
use Nexus\AccountVarianceAnalysis\Services\TrendAnalyzer;
use Nexus\AccountVarianceAnalysis\Services\AttributionAnalyzer;
use Nexus\AccountVarianceAnalysis\ValueObjects\VarianceResult;

/**
 * Coordinator for variance report generation.
 */
final readonly class VarianceReportCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private BudgetDataProvider $dataProvider,
        private VarianceCalculator $calculator,
        private TrendAnalyzer $trendAnalyzer,
        private AttributionAnalyzer $attributionAnalyzer,
    ) {}

    public function getName(): string
    {
        return 'variance_report';
    }

    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function getSupportedOperations(): array
    {
        return ['generate', 'compare', 'analyze_trends'];
    }

    /**
     * @return array<string, VarianceResult>
     */
    public function generate(VarianceReportRequest $request): array
    {
        $actuals = $this->dataProvider->getActualAmounts(
            $request->tenantId,
            $request->periodId
        );

        $comparatives = [];

        if ($request->budgetId !== null) {
            $comparatives = $this->dataProvider->getBudgetAmounts(
                $request->tenantId,
                $request->periodId,
                $request->budgetId
            );
        } else {
            $comparatives = $this->dataProvider->getPriorPeriodAmounts(
                $request->tenantId,
                $request->comparativePeriodId
            );
        }

        $results = [];
        foreach ($actuals as $accountId => $actualAmount) {
            $comparativeAmount = $comparatives[$accountId] ?? 0.0;
            $results[$accountId] = $this->calculator->calculate(
                $accountId,
                $actualAmount,
                $comparativeAmount,
                $request->varianceType
            );
        }

        return $results;
    }
}
