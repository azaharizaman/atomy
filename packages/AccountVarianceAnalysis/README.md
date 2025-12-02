# Nexus\AccountVarianceAnalysis

**Framework-Agnostic Financial Variance Analysis Engine**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\AccountVarianceAnalysis` is a pure PHP package that provides the core engine for analyzing financial variances between actual results and budgets, forecasts, or prior periods. It calculates variances, identifies significant deviations, performs trend analysis, and provides attribution analysis to explain the drivers of variances.

This package is **framework-agnostic** and contains no database access, no HTTP controllers, and no framework-specific code. Consuming applications provide comparative data through injected interfaces.

## Installation

```bash
composer require nexus/account-variance-analysis
```

## Package Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Variance Calculation** | Calculate differences between actual and budget/forecast |
| **Significance Evaluation** | Determine if variances exceed materiality thresholds |
| **Trend Analysis** | Identify patterns and trends over multiple periods |
| **Attribution Analysis** | Break down variances into contributing factors |
| **Statistical Analysis** | Provide statistical measures (std dev, moving averages) |
| **Rolling Forecasts** | Update forecasts based on actual results |

## Key Concepts

### Variance Types

| Type | Comparison | Use Case |
|------|------------|----------|
| **Budget Variance** | Actual vs Budget | Annual/quarterly budget comparison |
| **Forecast Variance** | Actual vs Forecast | Rolling forecast accuracy |
| **Prior Period** | Current vs Prior | Period-over-period analysis |
| **Year-over-Year** | Current YTD vs Prior YTD | Annual trend analysis |
| **Plan Variance** | Actual vs Strategic Plan | Long-term goal tracking |

### Significance Levels

- **Critical** - Variance exceeds 3x threshold
- **High** - Variance exceeds 2x threshold
- **Medium** - Variance exceeds 1.5x threshold
- **Low** - Variance exceeds threshold
- **Insignificant** - Within acceptable range

---

## Architecture

```
src/
├── Contracts/           # Interfaces defining the public API
├── ValueObjects/        # Immutable variance data structures
├── Enums/               # Variance types, statuses, directions
├── Services/            # Core variance calculation logic
└── Exceptions/          # Domain-specific errors
```

---

## Contracts (Interfaces)

### Core Interfaces

#### `VarianceCalculatorInterface`

The main entry point for variance calculations.

```php
interface VarianceCalculatorInterface
{
    /**
     * Calculate variance between actual and comparison values
     *
     * @param Money $actual The actual amount
     * @param Money $comparison The budget/forecast/prior amount
     * @param VarianceType $type The type of variance calculation
     * @return VarianceResult
     */
    public function calculate(
        Money $actual,
        Money $comparison,
        VarianceType $type = VarianceType::BUDGET
    ): VarianceResult;
    
    /**
     * Calculate variances for multiple accounts
     *
     * @param array<AccountVariance> $accounts
     * @return array<VarianceResult>
     */
    public function calculateBatch(array $accounts): array;
    
    /**
     * Calculate favorable/unfavorable direction
     * (Revenue: over = favorable; Expense: under = favorable)
     */
    public function determineDirection(
        VarianceResult $variance,
        AccountCategory $category
    ): VarianceDirection;
}
```

#### `TrendAnalyzerInterface`

Analyzes trends across multiple periods.

```php
interface TrendAnalyzerInterface
{
    /**
     * Analyze trend for an account over multiple periods
     *
     * @param array<PeriodValue> $periodValues Historical values by period
     * @param int $periodsForward Periods to project forward
     * @return TrendData
     */
    public function analyze(array $periodValues, int $periodsForward = 3): TrendData;
    
    /**
     * Detect trend direction (up, down, stable)
     */
    public function detectTrendDirection(array $periodValues): TrendDirection;
    
    /**
     * Calculate moving average
     */
    public function calculateMovingAverage(
        array $periodValues,
        int $periods = 3
    ): array;
    
    /**
     * Identify seasonality patterns
     */
    public function detectSeasonality(
        array $monthlyValues,
        int $yearsOfData = 2
    ): SeasonalityPattern;
}
```

#### `SignificanceEvaluatorInterface`

Evaluates whether variances are significant.

```php
interface SignificanceEvaluatorInterface
{
    /**
     * Evaluate significance of a variance
     *
     * @param VarianceResult $variance The calculated variance
     * @param SignificanceThreshold $threshold Threshold configuration
     * @return SignificanceLevel
     */
    public function evaluate(
        VarianceResult $variance,
        SignificanceThreshold $threshold
    ): SignificanceLevel;
    
    /**
     * Check if variance exceeds any threshold
     */
    public function isSignificant(
        VarianceResult $variance,
        SignificanceThreshold $threshold
    ): bool;
    
    /**
     * Get all variances exceeding threshold
     *
     * @param array<VarianceResult> $variances
     * @return array<VarianceResult>
     */
    public function filterSignificant(
        array $variances,
        SignificanceThreshold $threshold
    ): array;
}
```

#### `AttributionAnalyzerInterface`

Breaks down variances into contributing factors.

```php
interface AttributionAnalyzerInterface
{
    /**
     * Attribute variance to contributing factors
     *
     * @param VarianceResult $variance The total variance
     * @param array<string, mixed> $factors Factor data for attribution
     * @return VarianceAttribution
     */
    public function attribute(
        VarianceResult $variance,
        array $factors
    ): VarianceAttribution;
    
    /**
     * Perform price/volume variance analysis
     * (Common for revenue and cost analysis)
     */
    public function priceVolumeAnalysis(
        float $actualPrice,
        float $budgetPrice,
        float $actualVolume,
        float $budgetVolume
    ): PriceVolumeBreakdown;
    
    /**
     * Attribute to organizational units
     */
    public function attributeByDimension(
        VarianceResult $variance,
        array $dimensionData,
        string $dimensionName
    ): array;
}
```

#### `VarianceDataProviderInterface`

Contract for consuming applications to provide data.

```php
interface VarianceDataProviderInterface
{
    /**
     * Get actual account balances for a period
     */
    public function getActualBalances(
        string $tenantId,
        string $periodId
    ): array;
    
    /**
     * Get budget amounts for a period
     */
    public function getBudgetAmounts(
        string $tenantId,
        string $periodId
    ): array;
    
    /**
     * Get forecast amounts for a period
     */
    public function getForecastAmounts(
        string $tenantId,
        string $periodId
    ): array;
    
    /**
     * Get prior period balances
     */
    public function getPriorPeriodBalances(
        string $tenantId,
        string $periodId,
        int $periodsBack = 1
    ): array;
    
    /**
     * Get historical values for trend analysis
     */
    public function getHistoricalValues(
        string $tenantId,
        string $accountId,
        int $periods = 12
    ): array;
}
```

---

## Value Objects

### `VarianceResult`

```php
final readonly class VarianceResult
{
    public function __construct(
        public string $accountId,
        public string $accountName,
        public Money $actualAmount,
        public Money $comparisonAmount,
        public Money $varianceAmount,
        public float $variancePercentage,
        public VarianceType $type,
        public VarianceStatus $status,
        public bool $isFavorable
    ) {}
    
    public function isOverBudget(): bool
    {
        return $this->varianceAmount->isPositive();
    }
    
    public function isUnderBudget(): bool
    {
        return $this->varianceAmount->isNegative();
    }
}
```

### `TrendData`

```php
final readonly class TrendData
{
    public function __construct(
        public string $accountId,
        public TrendDirection $direction,
        public float $averageGrowthRate,
        public float $standardDeviation,
        public array $movingAverages,
        public array $projectedValues,
        public float $rSquared,
        public float $trendSlope
    ) {}
    
    public function isVolatile(float $threshold = 0.15): bool
    {
        return $this->standardDeviation > $threshold;
    }
}
```

### `SignificanceThreshold`

```php
final readonly class SignificanceThreshold
{
    public function __construct(
        public float $percentageThreshold = 10.0,
        public Money $absoluteThreshold = null,
        public bool $usePercentage = true,
        public bool $useAbsolute = false,
        public bool $requireBoth = false
    ) {}
    
    public static function percentage(float $percent): self
    {
        return new self(percentageThreshold: $percent, usePercentage: true);
    }
    
    public static function absolute(Money $amount): self
    {
        return new self(absoluteThreshold: $amount, usePercentage: false, useAbsolute: true);
    }
}
```

### `VarianceAttribution`

```php
final readonly class VarianceAttribution
{
    public function __construct(
        public VarianceResult $totalVariance,
        public array $attributions,
        public float $explainedPercentage,
        public Money $unexplainedAmount
    ) {}
    
    /**
     * @return array<string, Money>
     */
    public function getTopContributors(int $count = 5): array
    {
        // Return top N contributing factors
    }
}
```

### `AccountVariance`

```php
final readonly class AccountVariance
{
    public function __construct(
        public string $accountId,
        public string $accountCode,
        public string $accountName,
        public AccountCategory $category,
        public Money $actualAmount,
        public Money $budgetAmount,
        public ?Money $priorPeriodAmount = null,
        public ?Money $forecastAmount = null
    ) {}
}
```

### `ForecastVariance`

```php
final readonly class ForecastVariance
{
    public function __construct(
        public string $accountId,
        public Money $originalForecast,
        public Money $revisedForecast,
        public Money $actualToDate,
        public float $forecastAccuracy,
        public string $revisionReason
    ) {}
}
```

---

## Enums

### `VarianceType`

```php
enum VarianceType: string
{
    case BUDGET = 'budget';
    case FORECAST = 'forecast';
    case PRIOR_PERIOD = 'prior_period';
    case YEAR_OVER_YEAR = 'year_over_year';
    case PLAN = 'plan';
}
```

### `VarianceStatus`

```php
enum VarianceStatus: string
{
    case FAVORABLE = 'favorable';
    case UNFAVORABLE = 'unfavorable';
    case ON_TARGET = 'on_target';
    case NOT_CALCULATED = 'not_calculated';
}
```

### `TrendDirection`

```php
enum TrendDirection: string
{
    case INCREASING = 'increasing';
    case DECREASING = 'decreasing';
    case STABLE = 'stable';
    case VOLATILE = 'volatile';
}
```

### `SignificanceLevel`

```php
enum SignificanceLevel: string
{
    case CRITICAL = 'critical';      // > 3x threshold
    case HIGH = 'high';              // > 2x threshold
    case MEDIUM = 'medium';          // > 1.5x threshold
    case LOW = 'low';                // > 1x threshold
    case INSIGNIFICANT = 'insignificant';
}
```

---

## Services

### `VarianceCalculator`

Core variance calculation logic:
- Amount variance (Actual - Budget)
- Percentage variance ((Actual - Budget) / Budget × 100)
- Favorable/unfavorable determination by account type
- Batch calculations for efficiency

### `TrendAnalyzer`

Trend analysis capabilities:
- Linear regression for trend line
- Moving average calculations (3, 6, 12 period)
- Growth rate calculations
- Seasonality detection
- Future value projections

### `SignificanceEvaluator`

Threshold evaluation:
- Percentage-based thresholds
- Absolute amount thresholds
- Combined threshold logic
- Significance level assignment

### `AttributionAnalyzer`

Variance attribution:
- Price/Volume analysis
- Mix variance
- Rate/Efficiency analysis
- Multi-dimensional attribution

### `StatisticalCalculator`

Statistical functions:
- Standard deviation
- Coefficient of variation
- Correlation analysis
- R-squared calculation

### `RollingForecastCalculator`

Forecast updates:
- Reforecast based on actuals
- Remaining period projections
- Accuracy tracking

---

## Exceptions

| Exception | When Thrown |
|-----------|-------------|
| `VarianceCalculationException` | Variance calculation fails (division by zero, etc.) |
| `InsufficientDataException` | Not enough data points for trend analysis |
| `InvalidThresholdException` | Threshold configuration is invalid |

---

## Usage Example

```php
use Nexus\AccountVarianceAnalysis\Contracts\VarianceCalculatorInterface;
use Nexus\AccountVarianceAnalysis\Contracts\SignificanceEvaluatorInterface;
use Nexus\AccountVarianceAnalysis\ValueObjects\SignificanceThreshold;
use Nexus\AccountVarianceAnalysis\Enums\VarianceType;

final readonly class BudgetVarianceReportService
{
    public function __construct(
        private VarianceCalculatorInterface $calculator,
        private SignificanceEvaluatorInterface $evaluator,
        private VarianceDataProviderInterface $dataProvider
    ) {}
    
    public function generateReport(string $tenantId, string $periodId): array
    {
        // Get actual and budget data
        $actuals = $this->dataProvider->getActualBalances($tenantId, $periodId);
        $budgets = $this->dataProvider->getBudgetAmounts($tenantId, $periodId);
        
        // Define significance threshold
        $threshold = SignificanceThreshold::percentage(10.0);
        
        $variances = [];
        $significantVariances = [];
        
        foreach ($actuals as $accountId => $actual) {
            $budget = $budgets[$accountId] ?? null;
            if ($budget === null) continue;
            
            // Calculate variance
            $variance = $this->calculator->calculate(
                actual: $actual,
                comparison: $budget,
                type: VarianceType::BUDGET
            );
            
            $variances[] = $variance;
            
            // Check significance
            if ($this->evaluator->isSignificant($variance, $threshold)) {
                $significantVariances[] = $variance;
            }
        }
        
        return [
            'all_variances' => $variances,
            'significant_variances' => $significantVariances,
            'total_favorable' => $this->sumFavorable($variances),
            'total_unfavorable' => $this->sumUnfavorable($variances),
        ];
    }
}
```

---

## Variance Analysis Formulas

### Basic Variance

```
Variance Amount = Actual - Budget
Variance % = ((Actual - Budget) / Budget) × 100
```

### Favorable/Unfavorable Logic

| Account Type | Over Budget | Under Budget |
|--------------|-------------|--------------|
| Revenue | Favorable ✅ | Unfavorable ❌ |
| Expense | Unfavorable ❌ | Favorable ✅ |
| Asset | Contextual | Contextual |
| Liability | Contextual | Contextual |

### Price/Volume Analysis

```
Total Variance = (Actual Price × Actual Volume) - (Budget Price × Budget Volume)

Price Variance = (Actual Price - Budget Price) × Actual Volume
Volume Variance = (Actual Volume - Budget Volume) × Budget Price
Mix Variance = Difference (if present)
```

---

## Integration with Other Packages

| Package | Integration |
|---------|-------------|
| `Nexus\Finance` | Provides actual GL balances |
| `Nexus\Budget` | Provides budget amounts |
| `Nexus\FinancialStatements` | Variance data in statement notes |
| `Nexus\Notifier` | Alert on significant variances |
| `Nexus\AuditLogger` | Log variance analysis events |

---

## Related Documentation

- [ARCHITECTURE.md](../../ARCHITECTURE.md) - Overall system architecture
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Coding standards
- [Nexus Packages Reference](../../docs/NEXUS_PACKAGES_REFERENCE.md) - All available packages

---

## License

MIT License - See [LICENSE](LICENSE) for details.
