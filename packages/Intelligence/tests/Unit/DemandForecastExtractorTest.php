<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Tests\Unit;

use DateTimeImmutable;
use Nexus\Intelligence\Extractors\DemandForecastExtractor;
use Nexus\Intelligence\Contracts\InventoryAnalyticsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('intelligence')]
#[Group('extractors')]
#[Group('inventory')]
final class DemandForecastExtractorTest extends TestCase
{
    private InventoryAnalyticsRepositoryInterface $repository;
    private DemandForecastExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(InventoryAnalyticsRepositoryInterface::class);
        $this->extractor = new DemandForecastExtractor($this->repository);
    }

    #[Test]
    public function it_extracts_all_22_features_successfully(): void
    {
        // Arrange: Mock repository responses
        $this->repository->method('getAverageDailyDemand30d')->willReturn(15.5);
        $this->repository->method('getAverageDailyDemand90d')->willReturn(14.8);
        $this->repository->method('getAverageDailyDemand365d')->willReturn(13.2);
        $this->repository->method('getDemandStdDev30d')->willReturn(4.2);
        $this->repository->method('getDemandStdDev90d')->willReturn(5.1);
        $this->repository->method('getDemandStdDev365d')->willReturn(6.8);
        $this->repository->method('getCoefficientOfVariation')->willReturn(0.35);
        $this->repository->method('getTrendSlope90d')->willReturn(0.15);
        $this->repository->method('getSeasonalityIndex')->willReturn(1.08);
        $this->repository->method('getCurrentStock')->willReturn(250.0);
        $this->repository->method('getDaysOnHand')->willReturn(16.1);
        $this->repository->method('getTurnoverRatio90d')->willReturn(5.6);
        $this->repository->method('getStockoutCount90d')->willReturn(2);
        $this->repository->method('getStockoutDays90d')->willReturn(5);
        $this->repository->method('getLeadTimeDays')->willReturn(7.0);
        $this->repository->method('getLeadTimeVariability')->willReturn(0.15);
        $this->repository->method('getEconomicOrderQuantity')->willReturn(180.0);
        $this->repository->method('getReorderPoint')->willReturn(120.0);
        $this->repository->method('getSafetyStock')->willReturn(45.0);
        $this->repository->method('getMaxDailyDemand90d')->willReturn(28.0);
        $this->repository->method('getMinDailyDemand90d')->willReturn(5.0);
        $this->repository->method('getDaysSinceLastSale')->willReturn(3);

        $context = [
            'product_id' => 'PROD-001',
            'forecast_horizon_days' => 30,
        ];

        // Act
        $features = $this->extractor->extract($context);

        // Assert: Verify all 22 features are present
        $this->assertCount(22, $features);
        
        // Verify time-series demand features
        $this->assertArrayHasKey('avg_daily_demand_30d', $features);
        $this->assertSame(15.5, $features['avg_daily_demand_30d']);
        
        $this->assertArrayHasKey('avg_daily_demand_90d', $features);
        $this->assertSame(14.8, $features['avg_daily_demand_90d']);
        
        $this->assertArrayHasKey('avg_daily_demand_365d', $features);
        $this->assertSame(13.2, $features['avg_daily_demand_365d']);
        
        // Verify variability features
        $this->assertArrayHasKey('demand_std_dev_30d', $features);
        $this->assertSame(4.2, $features['demand_std_dev_30d']);
        
        $this->assertArrayHasKey('coefficient_of_variation', $features);
        $this->assertSame(0.35, $features['coefficient_of_variation']);
        
        // Verify trend and seasonality
        $this->assertArrayHasKey('trend_slope_90d', $features);
        $this->assertSame(0.15, $features['trend_slope_90d']);
        
        $this->assertArrayHasKey('seasonality_index', $features);
        $this->assertSame(1.08, $features['seasonality_index']);
        
        // Verify inventory health features
        $this->assertArrayHasKey('current_stock_level', $features);
        $this->assertSame(250.0, $features['current_stock_level']);
        
        $this->assertArrayHasKey('days_on_hand', $features);
        $this->assertSame(16.1, $features['days_on_hand']);
        
        $this->assertArrayHasKey('turnover_ratio_90d', $features);
        $this->assertSame(5.6, $features['turnover_ratio_90d']);
        
        // Verify stockout features
        $this->assertArrayHasKey('stockout_count_90d', $features);
        $this->assertSame(2, $features['stockout_count_90d']);
        
        $this->assertArrayHasKey('stockout_days_90d', $features);
        $this->assertSame(5, $features['stockout_days_90d']);
        
        // Verify supply chain features
        $this->assertArrayHasKey('lead_time_days', $features);
        $this->assertSame(7.0, $features['lead_time_days']);
        
        $this->assertArrayHasKey('lead_time_variability', $features);
        $this->assertSame(0.15, $features['lead_time_variability']);
        
        // Verify optimization features
        $this->assertArrayHasKey('economic_order_quantity', $features);
        $this->assertSame(180.0, $features['economic_order_quantity']);
        
        $this->assertArrayHasKey('reorder_point', $features);
        $this->assertSame(120.0, $features['reorder_point']);
        
        $this->assertArrayHasKey('safety_stock', $features);
        $this->assertSame(45.0, $features['safety_stock']);
    }

    #[Test]
    public function it_handles_missing_product_id_gracefully(): void
    {
        $context = [
            'forecast_horizon_days' => 30,
        ];

        $this->repository->expects($this->never())->method('getAverageDailyDemand30d');

        $features = $this->extractor->extract($context);

        $this->assertIsArray($features);
        $this->assertEmpty($features);
    }

    #[Test]
    #[DataProvider('trendPatternProvider')]
    public function it_identifies_trend_patterns_correctly(
        float $trendSlope,
        string $expectedPattern
    ): void {
        $this->repository->method('getAverageDailyDemand30d')->willReturn(15.0);
        $this->repository->method('getAverageDailyDemand90d')->willReturn(14.0);
        $this->repository->method('getAverageDailyDemand365d')->willReturn(12.0);
        $this->repository->method('getDemandStdDev30d')->willReturn(3.0);
        $this->repository->method('getDemandStdDev90d')->willReturn(3.5);
        $this->repository->method('getDemandStdDev365d')->willReturn(4.0);
        $this->repository->method('getCoefficientOfVariation')->willReturn(0.25);
        $this->repository->method('getTrendSlope90d')->willReturn($trendSlope);
        $this->repository->method('getSeasonalityIndex')->willReturn(1.0);
        $this->repository->method('getCurrentStock')->willReturn(200.0);
        $this->repository->method('getDaysOnHand')->willReturn(13.3);
        $this->repository->method('getTurnoverRatio90d')->willReturn(6.8);
        $this->repository->method('getStockoutCount90d')->willReturn(0);
        $this->repository->method('getStockoutDays90d')->willReturn(0);
        $this->repository->method('getLeadTimeDays')->willReturn(7.0);
        $this->repository->method('getLeadTimeVariability')->willReturn(0.10);
        $this->repository->method('getEconomicOrderQuantity')->willReturn(150.0);
        $this->repository->method('getReorderPoint')->willReturn(100.0);
        $this->repository->method('getSafetyStock')->willReturn(35.0);
        $this->repository->method('getMaxDailyDemand90d')->willReturn(25.0);
        $this->repository->method('getMinDailyDemand90d')->willReturn(8.0);
        $this->repository->method('getDaysSinceLastSale')->willReturn(2);

        $context = [
            'product_id' => 'PROD-001',
            'forecast_horizon_days' => 30,
        ];

        $features = $this->extractor->extract($context);

        $this->assertSame($trendSlope, $features['trend_slope_90d']);
        
        // Verify trend direction
        if ($expectedPattern === 'growing') {
            $this->assertGreaterThan(0, $features['trend_slope_90d']);
        } elseif ($expectedPattern === 'declining') {
            $this->assertLessThan(0, $features['trend_slope_90d']);
        } else {
            $this->assertEqualsWithDelta(0, $features['trend_slope_90d'], 0.05);
        }
    }

    public static function trendPatternProvider(): array
    {
        return [
            'Strong growth' => [0.50, 'growing'],
            'Moderate growth' => [0.15, 'growing'],
            'Stable demand' => [0.02, 'stable'],
            'Moderate decline' => [-0.12, 'declining'],
            'Strong decline' => [-0.40, 'declining'],
        ];
    }

    #[Test]
    #[DataProvider('variabilityProvider')]
    public function it_assesses_demand_variability_correctly(
        float $coefficientOfVariation,
        string $expectedStability
    ): void {
        $this->repository->method('getAverageDailyDemand30d')->willReturn(20.0);
        $this->repository->method('getAverageDailyDemand90d')->willReturn(19.0);
        $this->repository->method('getAverageDailyDemand365d')->willReturn(18.0);
        $this->repository->method('getDemandStdDev30d')->willReturn(5.0);
        $this->repository->method('getDemandStdDev90d')->willReturn(5.5);
        $this->repository->method('getDemandStdDev365d')->willReturn(6.0);
        $this->repository->method('getCoefficientOfVariation')->willReturn($coefficientOfVariation);
        $this->repository->method('getTrendSlope90d')->willReturn(0.05);
        $this->repository->method('getSeasonalityIndex')->willReturn(1.0);
        $this->repository->method('getCurrentStock')->willReturn(300.0);
        $this->repository->method('getDaysOnHand')->willReturn(15.8);
        $this->repository->method('getTurnoverRatio90d')->willReturn(5.7);
        $this->repository->method('getStockoutCount90d')->willReturn(1);
        $this->repository->method('getStockoutDays90d')->willReturn(2);
        $this->repository->method('getLeadTimeDays')->willReturn(10.0);
        $this->repository->method('getLeadTimeVariability')->willReturn(0.20);
        $this->repository->method('getEconomicOrderQuantity')->willReturn(200.0);
        $this->repository->method('getReorderPoint')->willReturn(140.0);
        $this->repository->method('getSafetyStock')->willReturn(60.0);
        $this->repository->method('getMaxDailyDemand90d')->willReturn(35.0);
        $this->repository->method('getMinDailyDemand90d')->willReturn(10.0);
        $this->repository->method('getDaysSinceLastSale')->willReturn(1);

        $context = [
            'product_id' => 'PROD-001',
            'forecast_horizon_days' => 30,
        ];

        $features = $this->extractor->extract($context);

        $this->assertSame($coefficientOfVariation, $features['coefficient_of_variation']);
        
        // Assess demand stability based on CV
        if ($expectedStability === 'stable') {
            $this->assertLessThan(0.30, $features['coefficient_of_variation']);
        } elseif ($expectedStability === 'moderate') {
            $this->assertGreaterThanOrEqual(0.30, $features['coefficient_of_variation']);
            $this->assertLessThan(0.60, $features['coefficient_of_variation']);
        } else { // volatile
            $this->assertGreaterThanOrEqual(0.60, $features['coefficient_of_variation']);
        }
    }

    public static function variabilityProvider(): array
    {
        return [
            'Very stable demand (CV < 0.20)' => [0.15, 'stable'],
            'Stable demand (CV < 0.30)' => [0.25, 'stable'],
            'Moderate variability (CV 0.30-0.60)' => [0.45, 'moderate'],
            'High variability (CV > 0.60)' => [0.75, 'volatile'],
            'Very high variability (CV > 1.0)' => [1.20, 'volatile'],
        ];
    }

    #[Test]
    public function it_handles_new_product_with_minimal_history(): void
    {
        $this->repository->method('getAverageDailyDemand30d')->willReturn(0.0);
        $this->repository->method('getAverageDailyDemand90d')->willReturn(0.0);
        $this->repository->method('getAverageDailyDemand365d')->willReturn(0.0);
        $this->repository->method('getDemandStdDev30d')->willReturn(0.0);
        $this->repository->method('getDemandStdDev90d')->willReturn(0.0);
        $this->repository->method('getDemandStdDev365d')->willReturn(0.0);
        $this->repository->method('getCoefficientOfVariation')->willReturn(0.0);
        $this->repository->method('getTrendSlope90d')->willReturn(0.0);
        $this->repository->method('getSeasonalityIndex')->willReturn(1.0);
        $this->repository->method('getCurrentStock')->willReturn(100.0);
        $this->repository->method('getDaysOnHand')->willReturn(0.0);
        $this->repository->method('getTurnoverRatio90d')->willReturn(0.0);
        $this->repository->method('getStockoutCount90d')->willReturn(0);
        $this->repository->method('getStockoutDays90d')->willReturn(0);
        $this->repository->method('getLeadTimeDays')->willReturn(14.0);
        $this->repository->method('getLeadTimeVariability')->willReturn(0.0);
        $this->repository->method('getEconomicOrderQuantity')->willReturn(0.0);
        $this->repository->method('getReorderPoint')->willReturn(0.0);
        $this->repository->method('getSafetyStock')->willReturn(0.0);
        $this->repository->method('getMaxDailyDemand90d')->willReturn(0.0);
        $this->repository->method('getMinDailyDemand90d')->willReturn(0.0);
        $this->repository->method('getDaysSinceLastSale')->willReturn(999);

        $context = [
            'product_id' => 'PROD-NEW',
            'forecast_horizon_days' => 30,
        ];

        $features = $this->extractor->extract($context);

        // Should extract features with zero/default values for new products
        $this->assertCount(22, $features);
        $this->assertSame(0.0, $features['avg_daily_demand_30d']);
        $this->assertSame(0.0, $features['turnover_ratio_90d']);
        $this->assertSame(999, $features['days_since_last_sale']);
    }

    #[Test]
    #[DataProvider('stockoutRiskProvider')]
    public function it_assesses_stockout_risk_correctly(
        int $stockoutCount,
        int $stockoutDays,
        float $daysOnHand,
        string $expectedRisk
    ): void {
        $this->repository->method('getAverageDailyDemand30d')->willReturn(18.0);
        $this->repository->method('getAverageDailyDemand90d')->willReturn(17.5);
        $this->repository->method('getAverageDailyDemand365d')->willReturn(16.0);
        $this->repository->method('getDemandStdDev30d')->willReturn(4.5);
        $this->repository->method('getDemandStdDev90d')->willReturn(5.0);
        $this->repository->method('getDemandStdDev365d')->willReturn(5.5);
        $this->repository->method('getCoefficientOfVariation')->willReturn(0.28);
        $this->repository->method('getTrendSlope90d')->willReturn(0.08);
        $this->repository->method('getSeasonalityIndex')->willReturn(1.05);
        $this->repository->method('getCurrentStock')->willReturn($daysOnHand * 18.0);
        $this->repository->method('getDaysOnHand')->willReturn($daysOnHand);
        $this->repository->method('getTurnoverRatio90d')->willReturn(5.0);
        $this->repository->method('getStockoutCount90d')->willReturn($stockoutCount);
        $this->repository->method('getStockoutDays90d')->willReturn($stockoutDays);
        $this->repository->method('getLeadTimeDays')->willReturn(7.0);
        $this->repository->method('getLeadTimeVariability')->willReturn(0.15);
        $this->repository->method('getEconomicOrderQuantity')->willReturn(180.0);
        $this->repository->method('getReorderPoint')->willReturn(140.0);
        $this->repository->method('getSafetyStock')->willReturn(50.0);
        $this->repository->method('getMaxDailyDemand90d')->willReturn(30.0);
        $this->repository->method('getMinDailyDemand90d')->willReturn(10.0);
        $this->repository->method('getDaysSinceLastSale')->willReturn(2);

        $context = [
            'product_id' => 'PROD-001',
            'forecast_horizon_days' => 30,
        ];

        $features = $this->extractor->extract($context);

        $this->assertSame($stockoutCount, $features['stockout_count_90d']);
        $this->assertSame($stockoutDays, $features['stockout_days_90d']);
        $this->assertSame($daysOnHand, $features['days_on_hand']);
        
        // Assess stockout risk
        if ($expectedRisk === 'high') {
            $this->assertTrue(
                $features['stockout_count_90d'] >= 3 || 
                $features['days_on_hand'] < 7
            );
        } elseif ($expectedRisk === 'low') {
            $this->assertTrue(
                $features['stockout_count_90d'] === 0 && 
                $features['days_on_hand'] > 14
            );
        }
    }

    public static function stockoutRiskProvider(): array
    {
        return [
            'Low risk (no stockouts, high inventory)' => [0, 0, 20.0, 'low'],
            'Low risk (rare stockout, adequate inventory)' => [1, 2, 15.0, 'low'],
            'Medium risk (some stockouts, moderate inventory)' => [2, 5, 10.0, 'medium'],
            'High risk (frequent stockouts, low inventory)' => [4, 12, 5.0, 'high'],
            'Very high risk (critical stockouts, very low inventory)' => [6, 18, 3.0, 'high'],
        ];
    }

    #[Test]
    public function it_calculates_safety_stock_based_on_lead_time_and_variability(): void
    {
        $leadTime = 10.0;
        $leadTimeVariability = 0.25;
        $avgDemand = 20.0;
        $demandStdDev = 6.0;
        
        $this->repository->method('getAverageDailyDemand30d')->willReturn($avgDemand);
        $this->repository->method('getAverageDailyDemand90d')->willReturn($avgDemand);
        $this->repository->method('getAverageDailyDemand365d')->willReturn($avgDemand);
        $this->repository->method('getDemandStdDev30d')->willReturn($demandStdDev);
        $this->repository->method('getDemandStdDev90d')->willReturn($demandStdDev);
        $this->repository->method('getDemandStdDev365d')->willReturn($demandStdDev);
        $this->repository->method('getCoefficientOfVariation')->willReturn(0.30);
        $this->repository->method('getTrendSlope90d')->willReturn(0.10);
        $this->repository->method('getSeasonalityIndex')->willReturn(1.0);
        $this->repository->method('getCurrentStock')->willReturn(400.0);
        $this->repository->method('getDaysOnHand')->willReturn(20.0);
        $this->repository->method('getTurnoverRatio90d')->willReturn(4.5);
        $this->repository->method('getStockoutCount90d')->willReturn(1);
        $this->repository->method('getStockoutDays90d')->willReturn(3);
        $this->repository->method('getLeadTimeDays')->willReturn($leadTime);
        $this->repository->method('getLeadTimeVariability')->willReturn($leadTimeVariability);
        $this->repository->method('getEconomicOrderQuantity')->willReturn(250.0);
        $this->repository->method('getReorderPoint')->willReturn(200.0);
        $this->repository->method('getSafetyStock')->willReturn(80.0);
        $this->repository->method('getMaxDailyDemand90d')->willReturn(32.0);
        $this->repository->method('getMinDailyDemand90d')->willReturn(12.0);
        $this->repository->method('getDaysSinceLastSale')->willReturn(1);

        $context = [
            'product_id' => 'PROD-001',
            'forecast_horizon_days' => 30,
        ];

        $features = $this->extractor->extract($context);

        // Safety stock should increase with lead time and variability
        $this->assertSame(80.0, $features['safety_stock']);
        $this->assertSame($leadTime, $features['lead_time_days']);
        $this->assertSame($leadTimeVariability, $features['lead_time_variability']);
        
        // Reorder point should be lead time demand + safety stock
        $this->assertGreaterThan($leadTime * $avgDemand, $features['reorder_point']);
    }
}
