# Nexus\FinancialRatios

**Framework-Agnostic Financial Ratio Analysis Engine**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\FinancialRatios` is a pure PHP package that provides the core engine for calculating and analyzing financial ratios. It covers liquidity, profitability, leverage, efficiency, cash flow, and market ratios. The package includes DuPont analysis decomposition and benchmarking capabilities.

This package is **framework-agnostic** and contains no database access, no HTTP controllers, and no framework-specific code. Consuming applications provide financial data through injected interfaces.

## Installation

```bash
composer require nexus/financial-ratios
```

## Package Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Ratio Calculation** | Calculate standard financial ratios |
| **DuPont Analysis** | Decompose ROE into component drivers |
| **Benchmarking** | Compare ratios against industry standards |
| **Health Assessment** | Evaluate financial health indicators |
| **Trend Analysis** | Track ratio changes over time |
| **Multi-Period Comparison** | Compare ratios across periods |

## Key Concepts

### Ratio Categories

| Category | Purpose | Key Ratios |
|----------|---------|------------|
| **Liquidity** | Short-term solvency | Current, Quick, Cash |
| **Profitability** | Earnings generation | Gross Margin, Net Margin, ROA, ROE |
| **Leverage** | Debt management | Debt-to-Equity, Interest Coverage |
| **Efficiency** | Asset utilization | Inventory Turnover, AR Turnover |
| **Cash Flow** | Cash generation | Operating Cash Flow, Free Cash Flow |
| **Market** | Investor metrics | EPS, P/E, Dividend Yield |

---

## Architecture

```
src/
├── Contracts/           # Interfaces defining the public API
├── ValueObjects/        # Immutable ratio data structures
├── Enums/               # Ratio categories and types
├── Services/            # Ratio calculation logic
└── Exceptions/          # Domain-specific errors
```

---

## Contracts (Interfaces)

### Core Interfaces

#### `RatioCalculatorInterface`

Unified interface for all ratio calculations.

```php
interface RatioCalculatorInterface
{
    /**
     * Calculate a specific ratio
     *
     * @param RatioType $type The ratio to calculate
     * @param RatioInput $input The financial data
     * @return RatioResult
     */
    public function calculate(RatioType $type, RatioInput $input): RatioResult;
    
    /**
     * Calculate all ratios in a category
     *
     * @return array<RatioResult>
     */
    public function calculateCategory(
        RatioCategory $category,
        RatioInput $input
    ): array;
    
    /**
     * Calculate all standard ratios
     *
     * @return array<RatioCategory, array<RatioResult>>
     */
    public function calculateAll(RatioInput $input): array;
}
```

#### `LiquidityRatioInterface`

Calculates short-term solvency ratios.

```php
interface LiquidityRatioInterface
{
    /**
     * Current Ratio = Current Assets / Current Liabilities
     * Measures ability to pay short-term obligations
     */
    public function currentRatio(Money $currentAssets, Money $currentLiabilities): RatioResult;
    
    /**
     * Quick Ratio = (Current Assets - Inventory) / Current Liabilities
     * Excludes less liquid inventory
     */
    public function quickRatio(
        Money $currentAssets,
        Money $inventory,
        Money $currentLiabilities
    ): RatioResult;
    
    /**
     * Cash Ratio = Cash & Equivalents / Current Liabilities
     * Most conservative liquidity measure
     */
    public function cashRatio(Money $cashAndEquivalents, Money $currentLiabilities): RatioResult;
    
    /**
     * Working Capital = Current Assets - Current Liabilities
     * Absolute liquidity measure
     */
    public function workingCapital(Money $currentAssets, Money $currentLiabilities): Money;
}
```

#### `ProfitabilityRatioInterface`

Calculates earnings and margin ratios.

```php
interface ProfitabilityRatioInterface
{
    /**
     * Gross Profit Margin = Gross Profit / Revenue
     */
    public function grossProfitMargin(Money $grossProfit, Money $revenue): RatioResult;
    
    /**
     * Operating Margin = Operating Income / Revenue
     */
    public function operatingMargin(Money $operatingIncome, Money $revenue): RatioResult;
    
    /**
     * Net Profit Margin = Net Income / Revenue
     */
    public function netProfitMargin(Money $netIncome, Money $revenue): RatioResult;
    
    /**
     * Return on Assets = Net Income / Total Assets
     */
    public function returnOnAssets(Money $netIncome, Money $totalAssets): RatioResult;
    
    /**
     * Return on Equity = Net Income / Shareholders' Equity
     */
    public function returnOnEquity(Money $netIncome, Money $shareholdersEquity): RatioResult;
    
    /**
     * Return on Invested Capital = NOPAT / Invested Capital
     */
    public function returnOnInvestedCapital(Money $nopat, Money $investedCapital): RatioResult;
}
```

#### `LeverageRatioInterface`

Calculates debt and leverage ratios.

```php
interface LeverageRatioInterface
{
    /**
     * Debt-to-Equity = Total Debt / Shareholders' Equity
     */
    public function debtToEquity(Money $totalDebt, Money $shareholdersEquity): RatioResult;
    
    /**
     * Debt-to-Assets = Total Debt / Total Assets
     */
    public function debtToAssets(Money $totalDebt, Money $totalAssets): RatioResult;
    
    /**
     * Interest Coverage = EBIT / Interest Expense
     */
    public function interestCoverage(Money $ebit, Money $interestExpense): RatioResult;
    
    /**
     * Equity Multiplier = Total Assets / Shareholders' Equity
     * (Used in DuPont analysis)
     */
    public function equityMultiplier(Money $totalAssets, Money $shareholdersEquity): RatioResult;
    
    /**
     * Debt Service Coverage = Operating Income / Total Debt Service
     */
    public function debtServiceCoverage(
        Money $operatingIncome,
        Money $totalDebtService
    ): RatioResult;
}
```

#### `EfficiencyRatioInterface`

Calculates asset utilization ratios.

```php
interface EfficiencyRatioInterface
{
    /**
     * Inventory Turnover = COGS / Average Inventory
     */
    public function inventoryTurnover(Money $cogs, Money $averageInventory): RatioResult;
    
    /**
     * Days Inventory Outstanding = 365 / Inventory Turnover
     */
    public function daysInventoryOutstanding(RatioResult $inventoryTurnover): RatioResult;
    
    /**
     * Receivables Turnover = Revenue / Average AR
     */
    public function receivablesTurnover(Money $revenue, Money $averageAR): RatioResult;
    
    /**
     * Days Sales Outstanding = 365 / Receivables Turnover
     */
    public function daysSalesOutstanding(RatioResult $receivablesTurnover): RatioResult;
    
    /**
     * Payables Turnover = COGS / Average AP
     */
    public function payablesTurnover(Money $cogs, Money $averageAP): RatioResult;
    
    /**
     * Days Payables Outstanding = 365 / Payables Turnover
     */
    public function daysPayablesOutstanding(RatioResult $payablesTurnover): RatioResult;
    
    /**
     * Asset Turnover = Revenue / Total Assets
     */
    public function assetTurnover(Money $revenue, Money $totalAssets): RatioResult;
    
    /**
     * Cash Conversion Cycle = DIO + DSO - DPO
     */
    public function cashConversionCycle(
        RatioResult $dio,
        RatioResult $dso,
        RatioResult $dpo
    ): RatioResult;
}
```

#### `CashFlowRatioInterface`

Calculates cash flow health ratios.

```php
interface CashFlowRatioInterface
{
    /**
     * Operating Cash Flow Ratio = Operating CF / Current Liabilities
     */
    public function operatingCashFlowRatio(
        Money $operatingCashFlow,
        Money $currentLiabilities
    ): RatioResult;
    
    /**
     * Free Cash Flow = Operating CF - CapEx
     */
    public function freeCashFlow(Money $operatingCashFlow, Money $capex): Money;
    
    /**
     * Cash Flow to Debt = Operating CF / Total Debt
     */
    public function cashFlowToDebt(Money $operatingCashFlow, Money $totalDebt): RatioResult;
    
    /**
     * Cash Flow Margin = Operating CF / Revenue
     */
    public function cashFlowMargin(Money $operatingCashFlow, Money $revenue): RatioResult;
    
    /**
     * Capital Expenditure Coverage = Operating CF / CapEx
     */
    public function capexCoverage(Money $operatingCashFlow, Money $capex): RatioResult;
}
```

#### `MarketRatioInterface`

Calculates investor and market ratios.

```php
interface MarketRatioInterface
{
    /**
     * Earnings Per Share = Net Income / Shares Outstanding
     */
    public function earningsPerShare(Money $netIncome, int $sharesOutstanding): RatioResult;
    
    /**
     * Price-to-Earnings = Market Price / EPS
     */
    public function priceToEarnings(Money $marketPrice, RatioResult $eps): RatioResult;
    
    /**
     * Price-to-Book = Market Price / Book Value Per Share
     */
    public function priceToBook(Money $marketPrice, Money $bookValuePerShare): RatioResult;
    
    /**
     * Dividend Yield = Dividend Per Share / Market Price
     */
    public function dividendYield(Money $dividendPerShare, Money $marketPrice): RatioResult;
    
    /**
     * Dividend Payout Ratio = Dividends / Net Income
     */
    public function dividendPayoutRatio(Money $dividends, Money $netIncome): RatioResult;
}
```

#### `RatioDataProviderInterface`

Contract for consuming applications to provide financial data.

```php
interface RatioDataProviderInterface
{
    /**
     * Get balance sheet data for ratio calculation
     */
    public function getBalanceSheetData(
        string $tenantId,
        string $periodId
    ): BalanceSheetData;
    
    /**
     * Get income statement data
     */
    public function getIncomeStatementData(
        string $tenantId,
        string $periodId
    ): IncomeStatementData;
    
    /**
     * Get cash flow data
     */
    public function getCashFlowData(
        string $tenantId,
        string $periodId
    ): CashFlowData;
    
    /**
     * Get market data (for public companies)
     */
    public function getMarketData(
        string $tenantId,
        string $periodId
    ): ?MarketData;
    
    /**
     * Get historical ratio values for trends
     */
    public function getHistoricalRatios(
        string $tenantId,
        RatioType $type,
        int $periods = 12
    ): array;
}
```

---

## Value Objects

### `RatioResult`

```php
final readonly class RatioResult
{
    public function __construct(
        public RatioType $type,
        public float $value,
        public string $displayValue,
        public RatioCategory $category,
        public string $interpretation,
        public ?\DateTimeImmutable $calculatedFor = null
    ) {}
    
    public function isHealthy(): bool
    {
        // Based on ratio type and value
    }
    
    public function compareToIndustry(float $industryAverage): string
    {
        // Above average, below average, etc.
    }
}
```

### `RatioInput`

```php
final readonly class RatioInput
{
    public function __construct(
        // Balance Sheet items
        public Money $currentAssets,
        public Money $totalAssets,
        public Money $currentLiabilities,
        public Money $totalLiabilities,
        public Money $totalDebt,
        public Money $shareholdersEquity,
        public Money $inventory,
        public Money $accountsReceivable,
        public Money $accountsPayable,
        public Money $cashAndEquivalents,
        
        // Income Statement items
        public Money $revenue,
        public Money $costOfGoodsSold,
        public Money $grossProfit,
        public Money $operatingIncome,
        public Money $netIncome,
        public Money $interestExpense,
        
        // Cash Flow items
        public Money $operatingCashFlow,
        public Money $capitalExpenditures,
        
        // Average values (for turnover ratios)
        public ?Money $averageInventory = null,
        public ?Money $averageReceivables = null,
        public ?Money $averagePayables = null,
        public ?Money $averageAssets = null
    ) {}
}
```

### `RatioBenchmark`

```php
final readonly class RatioBenchmark
{
    public function __construct(
        public RatioType $ratioType,
        public float $industryAverage,
        public float $industryMedian,
        public float $topQuartile,
        public float $bottomQuartile,
        public string $industryCode,
        public string $industryName,
        public BenchmarkSource $source,
        public \DateTimeImmutable $asOfDate
    ) {}
}
```

### `HealthIndicator`

```php
final readonly class HealthIndicator
{
    public function __construct(
        public string $area,
        public string $status,
        public string $summary,
        public array $concerningRatios,
        public array $healthyRatios,
        public array $recommendations
    ) {}
}
```

---

## Enums

### `RatioCategory`

```php
enum RatioCategory: string
{
    case LIQUIDITY = 'liquidity';
    case PROFITABILITY = 'profitability';
    case LEVERAGE = 'leverage';
    case EFFICIENCY = 'efficiency';
    case CASH_FLOW = 'cash_flow';
    case MARKET = 'market';
}
```

### `RatioType`

```php
enum RatioType: string
{
    // Liquidity
    case CURRENT_RATIO = 'current_ratio';
    case QUICK_RATIO = 'quick_ratio';
    case CASH_RATIO = 'cash_ratio';
    
    // Profitability
    case GROSS_PROFIT_MARGIN = 'gross_profit_margin';
    case OPERATING_MARGIN = 'operating_margin';
    case NET_PROFIT_MARGIN = 'net_profit_margin';
    case RETURN_ON_ASSETS = 'return_on_assets';
    case RETURN_ON_EQUITY = 'return_on_equity';
    case RETURN_ON_INVESTED_CAPITAL = 'return_on_invested_capital';
    
    // Leverage
    case DEBT_TO_EQUITY = 'debt_to_equity';
    case DEBT_TO_ASSETS = 'debt_to_assets';
    case INTEREST_COVERAGE = 'interest_coverage';
    case EQUITY_MULTIPLIER = 'equity_multiplier';
    
    // Efficiency
    case INVENTORY_TURNOVER = 'inventory_turnover';
    case DAYS_INVENTORY_OUTSTANDING = 'days_inventory_outstanding';
    case RECEIVABLES_TURNOVER = 'receivables_turnover';
    case DAYS_SALES_OUTSTANDING = 'days_sales_outstanding';
    case PAYABLES_TURNOVER = 'payables_turnover';
    case DAYS_PAYABLES_OUTSTANDING = 'days_payables_outstanding';
    case ASSET_TURNOVER = 'asset_turnover';
    case CASH_CONVERSION_CYCLE = 'cash_conversion_cycle';
    
    // Cash Flow
    case OPERATING_CASH_FLOW_RATIO = 'operating_cash_flow_ratio';
    case FREE_CASH_FLOW = 'free_cash_flow';
    case CASH_FLOW_TO_DEBT = 'cash_flow_to_debt';
    case CASH_FLOW_MARGIN = 'cash_flow_margin';
    
    // Market
    case EARNINGS_PER_SHARE = 'earnings_per_share';
    case PRICE_TO_EARNINGS = 'price_to_earnings';
    case PRICE_TO_BOOK = 'price_to_book';
    case DIVIDEND_YIELD = 'dividend_yield';
    case DIVIDEND_PAYOUT_RATIO = 'dividend_payout_ratio';
}
```

### `BenchmarkSource`

```php
enum BenchmarkSource: string
{
    case INDUSTRY_REPORT = 'industry_report';
    case REGULATORY = 'regulatory';
    case INTERNAL = 'internal';
    case PEER_GROUP = 'peer_group';
}
```

---

## Services

### `LiquidityRatioCalculator`

Calculates all liquidity ratios:
- Current Ratio
- Quick Ratio (Acid Test)
- Cash Ratio
- Working Capital

### `ProfitabilityRatioCalculator`

Calculates all profitability ratios:
- Gross Profit Margin
- Operating Margin
- Net Profit Margin
- ROA, ROE, ROIC

### `LeverageRatioCalculator`

Calculates all leverage ratios:
- Debt-to-Equity
- Debt-to-Assets
- Interest Coverage
- Equity Multiplier

### `EfficiencyRatioCalculator`

Calculates all efficiency ratios:
- Inventory Turnover & DIO
- Receivables Turnover & DSO
- Payables Turnover & DPO
- Asset Turnover
- Cash Conversion Cycle

### `CashFlowRatioCalculator`

Calculates all cash flow ratios:
- Operating Cash Flow Ratio
- Free Cash Flow
- Cash Flow to Debt
- Cash Flow Margin

### `MarketRatioCalculator`

Calculates all market ratios:
- EPS
- P/E Ratio
- P/B Ratio
- Dividend Yield

### `DuPontAnalyzer`

Performs DuPont analysis to decompose ROE:

```
ROE = Net Profit Margin × Asset Turnover × Equity Multiplier

Where:
- Net Profit Margin = Net Income / Revenue
- Asset Turnover = Revenue / Total Assets
- Equity Multiplier = Total Assets / Equity
```

3-Factor Model:
```
ROE = (Net Income / Revenue) × (Revenue / Assets) × (Assets / Equity)
```

5-Factor Extended Model:
```
ROE = Tax Burden × Interest Burden × Operating Margin × Asset Turnover × Leverage
```

### `RatioBenchmarker`

Compares calculated ratios against benchmarks:
- Industry averages
- Peer group comparisons
- Historical trends
- Regulatory minimums

---

## Exceptions

| Exception | When Thrown |
|-----------|-------------|
| `RatioCalculationException` | Ratio calculation fails (e.g., division by zero) |
| `BenchmarkNotFoundException` | No benchmark data available for comparison |
| `InsufficientDataException` | Not enough data to calculate ratio |

---

## Usage Example

```php
use Nexus\FinancialRatios\Contracts\RatioCalculatorInterface;
use Nexus\FinancialRatios\Services\DuPontAnalyzer;
use Nexus\FinancialRatios\ValueObjects\RatioInput;
use Nexus\FinancialRatios\Enums\RatioCategory;

final readonly class FinancialHealthReportService
{
    public function __construct(
        private RatioCalculatorInterface $calculator,
        private DuPontAnalyzer $dupontAnalyzer,
        private RatioDataProviderInterface $dataProvider
    ) {}
    
    public function generateHealthReport(string $tenantId, string $periodId): array
    {
        // Get financial data
        $balanceSheet = $this->dataProvider->getBalanceSheetData($tenantId, $periodId);
        $incomeStatement = $this->dataProvider->getIncomeStatementData($tenantId, $periodId);
        $cashFlow = $this->dataProvider->getCashFlowData($tenantId, $periodId);
        
        // Build ratio input
        $input = new RatioInput(
            currentAssets: $balanceSheet->currentAssets,
            totalAssets: $balanceSheet->totalAssets,
            // ... other inputs
        );
        
        // Calculate all ratios
        $allRatios = $this->calculator->calculateAll($input);
        
        // Perform DuPont analysis
        $dupont = $this->dupontAnalyzer->analyze($input);
        
        return [
            'liquidity' => $allRatios[RatioCategory::LIQUIDITY->value],
            'profitability' => $allRatios[RatioCategory::PROFITABILITY->value],
            'leverage' => $allRatios[RatioCategory::LEVERAGE->value],
            'efficiency' => $allRatios[RatioCategory::EFFICIENCY->value],
            'cash_flow' => $allRatios[RatioCategory::CASH_FLOW->value],
            'dupont_analysis' => $dupont,
            'health_score' => $this->calculateOverallHealth($allRatios),
        ];
    }
}
```

---

## Standard Ratio Formulas

### Liquidity Ratios

| Ratio | Formula | Healthy Range |
|-------|---------|---------------|
| Current Ratio | Current Assets / Current Liabilities | 1.5 - 3.0 |
| Quick Ratio | (Current Assets - Inventory) / Current Liabilities | 1.0 - 2.0 |
| Cash Ratio | Cash / Current Liabilities | 0.5 - 1.0 |

### Profitability Ratios

| Ratio | Formula | Interpretation |
|-------|---------|----------------|
| Gross Margin | Gross Profit / Revenue | Higher = better |
| Net Margin | Net Income / Revenue | Higher = better |
| ROA | Net Income / Total Assets | Higher = better |
| ROE | Net Income / Equity | 15-20%+ is good |

### Leverage Ratios

| Ratio | Formula | Healthy Range |
|-------|---------|---------------|
| Debt-to-Equity | Total Debt / Equity | < 2.0 typically |
| Interest Coverage | EBIT / Interest Expense | > 3.0 is healthy |

### Efficiency Ratios

| Ratio | Formula | Interpretation |
|-------|---------|----------------|
| Inventory Turnover | COGS / Avg Inventory | Higher = better |
| DSO | 365 / AR Turnover | Lower = faster collection |
| Cash Conversion Cycle | DIO + DSO - DPO | Lower = better |

---

## Integration with Other Packages

| Package | Integration |
|---------|-------------|
| `Nexus\FinancialStatements` | Provides statement data for calculations |
| `Nexus\Finance` | Provides account balances |
| `Nexus\AccountVarianceAnalysis` | Ratio trend analysis |
| `Nexus\Reporting` | Ratio reports generation |

---

## Related Documentation

- [ARCHITECTURE.md](../../ARCHITECTURE.md) - Overall system architecture
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Coding standards
- [Nexus Packages Reference](../../docs/NEXUS_PACKAGES_REFERENCE.md) - All available packages

---

## License

MIT License - See [LICENSE](LICENSE) for details.
