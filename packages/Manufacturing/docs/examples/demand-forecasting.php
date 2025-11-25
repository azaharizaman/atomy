<?php

declare(strict_types=1);

/**
 * Demand Forecasting Example
 *
 * This example demonstrates demand forecasting including
 * ML-powered predictions, historical analysis, and graceful fallbacks.
 */

use Nexus\Manufacturing\Services\DemandForecaster;
use Nexus\Manufacturing\Contracts\ForecastProviderInterface;
use Nexus\Manufacturing\ValueObjects\DemandForecast;
use Nexus\Manufacturing\Enums\ForecastConfidence;

// =============================================================================
// Setup - In your application, these would be injected via DI
// =============================================================================

/** @var DemandForecaster $demandForecaster */
/** @var ForecastProviderInterface $mlProvider */

// =============================================================================
// Example 1: Get Demand Forecast
// =============================================================================

echo "Example 1: Basic Demand Forecast\n";
echo str_repeat('-', 50) . "\n";

$productId = 'WIDGET-001';
$forecastDate = new DateTimeImmutable('+30 days');

$forecast = $demandForecaster->getForecast($productId, $forecastDate);

if ($forecast) {
    echo "Forecast for {$productId} on {$forecastDate->format('Y-m-d')}:\n";
    echo "  Quantity: {$forecast->quantity} units\n";
    echo "  Confidence: {$forecast->confidence->value}\n";
    echo "  Source: {$forecast->source}\n";
} else {
    echo "No forecast available for {$productId}\n";
}

echo "\n";

// =============================================================================
// Example 2: Forecast Range
// =============================================================================

echo "Example 2: Forecast Range\n";
echo str_repeat('-', 50) . "\n";

$startDate = new DateTimeImmutable('+1 day');
$endDate = new DateTimeImmutable('+90 days');

$forecasts = $demandForecaster->getForecastRange(
    productId: $productId,
    startDate: $startDate,
    endDate: $endDate,
    bucketSizeDays: 7
);

echo "Weekly forecast for {$productId}:\n\n";
echo sprintf(
    "%-12s %12s %12s %15s\n",
    'Week',
    'Quantity',
    'Confidence',
    'Source'
);
echo str_repeat('-', 55) . "\n";

$totalForecast = 0.0;
foreach ($forecasts as $forecast) {
    $totalForecast += $forecast->quantity;
    
    $confidenceIcon = match ($forecast->confidence) {
        ForecastConfidence::HIGH => '🟢',
        ForecastConfidence::MEDIUM => '🟡',
        ForecastConfidence::LOW => '🟠',
        ForecastConfidence::UNKNOWN => '⚪',
    };
    
    echo sprintf(
        "%-12s %12.1f %s %-10s %15s\n",
        $forecast->forecastDate->format('Y-m-d'),
        $forecast->quantity,
        $confidenceIcon,
        $forecast->confidence->value,
        $forecast->source
    );
}

echo str_repeat('-', 55) . "\n";
echo sprintf("%-12s %12.1f\n", 'TOTAL', $totalForecast);

echo "\n";

// =============================================================================
// Example 3: ML-Powered Forecasting with Fallback
// =============================================================================

echo "Example 3: ML Forecasting with Fallback\n";
echo str_repeat('-', 50) . "\n";

// Configure forecaster with ML provider and fallback
$mlForecaster = new DemandForecaster(
    primaryProvider: $mlProvider,
    fallbackProvider: new HistoricalAverageProvider(),
    minimumConfidence: ForecastConfidence::MEDIUM
);

echo "Forecasting with ML model (fallback to historical if ML unavailable):\n\n";

// Try ML forecast
$mlForecast = $mlForecaster->getForecast($productId, $forecastDate);

if ($mlForecast && $mlForecast->source === 'ml_model') {
    echo "✅ ML Model provided forecast\n";
    echo "   Quantity: {$mlForecast->quantity}\n";
    echo "   Confidence: {$mlForecast->confidence->value}\n";
} else {
    echo "⚠️  ML Model unavailable, using fallback\n";
    echo "   Quantity: {$mlForecast->quantity}\n";
    echo "   Source: {$mlForecast->source}\n";
}

echo "\n";

// =============================================================================
// Example 4: Forecast Accuracy Analysis
// =============================================================================

echo "Example 4: Forecast Accuracy Analysis\n";
echo str_repeat('-', 50) . "\n";

// Compare past forecasts with actual demand
$accuracy = $demandForecaster->analyzeAccuracy(
    productId: $productId,
    startDate: new DateTimeImmutable('-90 days'),
    endDate: new DateTimeImmutable('today')
);

echo "Forecast accuracy for last 90 days:\n\n";
echo "  Mean Absolute Error (MAE): {$accuracy->mae} units\n";
echo "  Mean Absolute Percentage Error (MAPE): {$accuracy->mape}%\n";
echo "  Root Mean Square Error (RMSE): {$accuracy->rmse} units\n";
echo "  Bias: {$accuracy->bias} units\n";
echo "  Forecast Count: {$accuracy->forecastCount}\n";
echo "\n";

echo "Accuracy by confidence level:\n";
foreach ($accuracy->byConfidence as $level => $metrics) {
    echo "  {$level}: MAE={$metrics['mae']}, MAPE={$metrics['mape']}%\n";
}

echo "\n";

// =============================================================================
// Example 5: Seasonality Detection
// =============================================================================

echo "Example 5: Seasonality Analysis\n";
echo str_repeat('-', 50) . "\n";

$seasonality = $demandForecaster->analyzeSeasonality($productId);

echo "Seasonality analysis for {$productId}:\n\n";

if ($seasonality->hasSeasonality()) {
    echo "📊 Seasonal pattern detected!\n";
    echo "   Pattern Type: {$seasonality->getPatternType()}\n";
    echo "   Cycle Length: {$seasonality->getCycleLength()} periods\n";
    echo "\n";
    
    echo "Monthly Seasonal Indices:\n";
    foreach ($seasonality->getMonthlyIndices() as $month => $index) {
        $bar = str_repeat('█', (int)($index * 20));
        $monthName = DateTime::createFromFormat('!m', (string)$month)->format('M');
        echo sprintf("   %s: %5.2f %s\n", $monthName, $index, $bar);
    }
} else {
    echo "📊 No significant seasonal pattern detected.\n";
}

echo "\n";

// =============================================================================
// Example 6: Trend Analysis
// =============================================================================

echo "Example 6: Trend Analysis\n";
echo str_repeat('-', 50) . "\n";

$trend = $demandForecaster->analyzeTrend($productId, periods: 12);

echo "Trend analysis for {$productId} (12 months):\n\n";
echo "  Trend Direction: {$trend->direction}\n";
echo "  Growth Rate: {$trend->growthRate}% per period\n";
echo "  Trend Strength: {$trend->strength}\n";
echo "  R-squared: {$trend->rSquared}\n";

if ($trend->direction === 'increasing') {
    echo "  📈 Demand is growing\n";
} elseif ($trend->direction === 'decreasing') {
    echo "  📉 Demand is declining\n";
} else {
    echo "  📊 Demand is stable\n";
}

echo "\n";

// =============================================================================
// Example 7: Aggregate Forecasts
// =============================================================================

echo "Example 7: Aggregate Product Family Forecast\n";
echo str_repeat('-', 50) . "\n";

$productFamily = ['WIDGET-001', 'WIDGET-002', 'WIDGET-003'];
$aggregateForecast = $demandForecaster->getAggregateForecast(
    productIds: $productFamily,
    startDate: new DateTimeImmutable('+1 day'),
    endDate: new DateTimeImmutable('+30 days')
);

echo "Aggregate forecast for WIDGET family:\n\n";
echo sprintf("%-15s %12s %12s\n", 'Product', 'Forecast', 'Share %');
echo str_repeat('-', 42) . "\n";

foreach ($aggregateForecast->byProduct as $productId => $data) {
    $share = ($data['quantity'] / $aggregateForecast->total) * 100;
    echo sprintf(
        "%-15s %12.1f %11.1f%%\n",
        $productId,
        $data['quantity'],
        $share
    );
}

echo str_repeat('-', 42) . "\n";
echo sprintf("%-15s %12.1f %11.1f%%\n", 'TOTAL', $aggregateForecast->total, 100.0);

echo "\n";

// =============================================================================
// Example 8: Export Forecast to MRP
// =============================================================================

echo "Example 8: Export Forecast for MRP\n";
echo str_repeat('-', 50) . "\n";

$mrpForecast = $demandForecaster->exportForMrp(
    productId: $productId,
    horizon: new PlanningHorizon(
        startDate: new DateTimeImmutable('today'),
        endDate: new DateTimeImmutable('+90 days'),
        bucketSizeDays: 7,
        frozenZoneDays: 14,
        slushyZoneDays: 28
    )
);

echo "Forecast exported for MRP processing:\n\n";
echo "  Product: {$productId}\n";
echo "  Periods: " . count($mrpForecast->getBuckets()) . "\n";
echo "  Total Forecasted Demand: {$mrpForecast->getTotalDemand()} units\n";
echo "  Average Confidence: {$mrpForecast->getAverageConfidence()}\n";

echo "\n";
echo "Demand forecasting example complete!\n";
