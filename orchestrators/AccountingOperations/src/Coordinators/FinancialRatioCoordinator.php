<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\DTOs\RatioAnalysisRequest;
use Nexus\FinancialRatios\Services\LiquidityRatioCalculator;
use Nexus\FinancialRatios\Services\ProfitabilityRatioCalculator;
use Nexus\FinancialRatios\Services\LeverageRatioCalculator;
use Nexus\FinancialRatios\Services\EfficiencyRatioCalculator;
use Nexus\FinancialRatios\Services\CashFlowRatioCalculator;
use Nexus\FinancialRatios\Services\MarketRatioCalculator;
use Nexus\FinancialRatios\ValueObjects\RatioResult;
use Nexus\FinancialRatios\ValueObjects\RatioInput;
use Nexus\FinancialRatios\Enums\RatioCategory;

/**
 * Coordinator for financial ratio analysis.
 */
final readonly class FinancialRatioCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private LiquidityRatioCalculator $liquidityCalculator,
        private ProfitabilityRatioCalculator $profitabilityCalculator,
        private LeverageRatioCalculator $leverageCalculator,
        private EfficiencyRatioCalculator $efficiencyCalculator,
        private CashFlowRatioCalculator $cashFlowCalculator,
        private MarketRatioCalculator $marketCalculator,
    ) {}

    public function getName(): string
    {
        return 'financial_ratios';
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
        return ['calculate_all', 'calculate_by_category', 'compare', 'benchmark'];
    }

    /**
     * @return array<string, array<string, RatioResult>>
     */
    public function analyze(RatioAnalysisRequest $request, RatioInput $input): array
    {
        $results = [];
        $categories = empty($request->categories)
            ? RatioCategory::cases()
            : $request->categories;

        foreach ($categories as $category) {
            $results[$category->value] = $this->calculateByCategory($category, $input);
        }

        return $results;
    }

    /**
     * @return array<string, RatioResult>
     */
    private function calculateByCategory(RatioCategory $category, RatioInput $input): array
    {
        return match ($category) {
            RatioCategory::LIQUIDITY => $this->calculateLiquidityRatios($input),
            RatioCategory::PROFITABILITY => $this->calculateProfitabilityRatios($input),
            RatioCategory::LEVERAGE => $this->calculateLeverageRatios($input),
            RatioCategory::EFFICIENCY => $this->calculateEfficiencyRatios($input),
            RatioCategory::CASH_FLOW => $this->calculateCashFlowRatios($input),
            RatioCategory::MARKET => [], // Requires market data
        };
    }

    /**
     * @return array<string, RatioResult>
     */
    private function calculateLiquidityRatios(RatioInput $input): array
    {
        return [
            'current_ratio' => $this->liquidityCalculator->currentRatio(
                $input->currentAssets,
                $input->currentLiabilities
            ),
            'quick_ratio' => $this->liquidityCalculator->quickRatio(
                $input->currentAssets,
                $input->inventory,
                $input->currentLiabilities
            ),
            'cash_ratio' => $this->liquidityCalculator->cashRatio(
                $input->cash,
                $input->currentLiabilities
            ),
        ];
    }

    /**
     * @return array<string, RatioResult>
     */
    private function calculateProfitabilityRatios(RatioInput $input): array
    {
        return [
            'gross_profit_margin' => $this->profitabilityCalculator->grossProfitMargin(
                $input->grossProfit,
                $input->revenue
            ),
            'operating_profit_margin' => $this->profitabilityCalculator->operatingProfitMargin(
                $input->operatingIncome,
                $input->revenue
            ),
            'net_profit_margin' => $this->profitabilityCalculator->netProfitMargin(
                $input->netIncome,
                $input->revenue
            ),
            'return_on_assets' => $this->profitabilityCalculator->returnOnAssets(
                $input->netIncome,
                $input->totalAssets
            ),
            'return_on_equity' => $this->profitabilityCalculator->returnOnEquity(
                $input->netIncome,
                $input->totalEquity
            ),
        ];
    }

    /**
     * @return array<string, RatioResult>
     */
    private function calculateLeverageRatios(RatioInput $input): array
    {
        return [
            'debt_to_equity' => $this->leverageCalculator->debtToEquity(
                $input->totalDebt,
                $input->totalEquity
            ),
            'debt_to_assets' => $this->leverageCalculator->debtToAssets(
                $input->totalDebt,
                $input->totalAssets
            ),
            'interest_coverage' => $this->leverageCalculator->interestCoverage(
                $input->getEbit(),
                $input->interestExpense
            ),
        ];
    }

    /**
     * @return array<string, RatioResult>
     */
    private function calculateEfficiencyRatios(RatioInput $input): array
    {
        return [
            'asset_turnover' => $this->efficiencyCalculator->assetTurnover(
                $input->revenue,
                $input->totalAssets
            ),
            'inventory_turnover' => $this->efficiencyCalculator->inventoryTurnover(
                $input->costOfGoodsSold,
                $input->inventory
            ),
            'receivables_turnover' => $this->efficiencyCalculator->receivablesTurnover(
                $input->revenue,
                $input->accountsReceivable
            ),
        ];
    }

    /**
     * @return array<string, RatioResult>
     */
    private function calculateCashFlowRatios(RatioInput $input): array
    {
        return [
            'operating_cash_flow_ratio' => $this->cashFlowCalculator->operatingCashFlowRatio(
                $input->operatingCashFlow,
                $input->currentLiabilities
            ),
            'cash_flow_margin' => $this->cashFlowCalculator->cashFlowMargin(
                $input->operatingCashFlow,
                $input->revenue
            ),
            'cash_flow_to_debt' => $this->cashFlowCalculator->cashFlowToDebt(
                $input->operatingCashFlow,
                $input->totalDebt
            ),
        ];
    }
}
