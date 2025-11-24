<?php

declare(strict_types=1);

namespace Nexus\Procurement\MachineLearning;

use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\Contracts\FeatureSetInterface;
use Nexus\MachineLearning\ValueObjects\FeatureSet;
use Nexus\Procurement\Contracts\PricingAnalyticsRepositoryInterface;

/**
 * Feature extractor for vendor pricing anomaly detection
 * 
 * Extracts 22 features to identify abnormal vendor pricing compared to
 * market rates, historical patterns, competitive quotes, and contract terms.
 * 
 * Usage: Real-time pricing validation on PO creation and vendor quote evaluation.
 */
final readonly class VendorPricingAnomalyExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';

    public function __construct(
        private PricingAnalyticsRepositoryInterface $pricingAnalytics
    ) {}

    /**
     * Extract pricing anomaly features from purchase order line
     * 
     * @param object $poLine Expected to have productId, vendorId, unitPrice, quantity, currency, etc.
     * @return FeatureSetInterface
     */
    public function extract(object $poLine): FeatureSetInterface
    {
        $productId = $poLine->productId ?? '';
        $vendorId = $poLine->vendorId ?? '';
        $unitPrice = (float)($poLine->unitPrice ?? 0.0);
        $quantity = (float)($poLine->quantity ?? 0.0);
        $currency = $poLine->currency ?? 'MYR';
        $categoryId = $poLine->categoryId ?? '';
        
        // Historical vendor pricing
        $vendorAvgPrice = $this->pricingAnalytics->getVendorAveragePrice($vendorId, $productId);
        $vendorStdPrice = $this->pricingAnalytics->getVendorStdPrice($vendorId, $productId);
        $vendorMinPrice = $this->pricingAnalytics->getVendorMinPrice($vendorId, $productId);
        $vendorMaxPrice = $this->pricingAnalytics->getVendorMaxPrice($vendorId, $productId);
        
        // Market benchmarks
        $marketAvgPrice = $this->pricingAnalytics->getMarketAveragePrice($productId);
        $marketStdPrice = $this->pricingAnalytics->getMarketStdPrice($productId);
        $categoryAvgPrice = $this->pricingAnalytics->getCategoryAveragePrice($categoryId);
        
        // Competitive analysis
        $competitiveQuotes = $this->pricingAnalytics->getRecentQuotesForProduct($productId);
        $quoteRank = $this->calculateQuoteRank($unitPrice, $competitiveQuotes);
        $cheapestQuote = count($competitiveQuotes) > 0 ? min(array_column($competitiveQuotes, 'price')) : 0.0;
        
        // Price velocity and trends
        $priceVelocity = $this->pricingAnalytics->getPriceVelocity($vendorId, $productId);
        $seasonalFactor = $this->pricingAnalytics->getSeasonalPriceFactor($productId);
        
        // Contract and volume discount validation
        $contractPrice = $this->pricingAnalytics->getContractPrice($vendorId, $productId);
        $volumeDiscountThreshold = $this->pricingAnalytics->getVolumeDiscountThreshold($vendorId, $productId);
        $expectedDiscount = $this->pricingAnalytics->getExpectedVolumeDiscount($vendorId, $productId, $quantity);
        
        // Currency and geographic factors
        $currencyVolatility = $this->pricingAnalytics->getCurrencyVolatility($currency);
        $geographicPriceVariance = $this->pricingAnalytics->getGeographicPriceVariance($productId);
        
        // Payment term impact
        $paymentTerms = $poLine->paymentTerms ?? 30;
        $termPriceImpact = $this->pricingAnalytics->getPaymentTermPriceImpact($vendorId, $paymentTerms);
        
        // Calculate engineered features
        $vendorPriceZScore = $vendorStdPrice > 0 ? ($unitPrice - $vendorAvgPrice) / $vendorStdPrice : 0.0;
        $marketPriceZScore = $marketStdPrice > 0 ? ($unitPrice - $marketAvgPrice) / $marketStdPrice : 0.0;
        $vendorPriceRatio = $vendorAvgPrice > 0 ? $unitPrice / $vendorAvgPrice : 1.0;
        $marketPriceRatio = $marketAvgPrice > 0 ? $unitPrice / $marketAvgPrice : 1.0;
        $categoryPriceRatio = $categoryAvgPrice > 0 ? $unitPrice / $categoryAvgPrice : 1.0;
        $contractDeviation = $contractPrice > 0 ? abs($unitPrice - $contractPrice) / $contractPrice : 0.0;
        $actualDiscount = $vendorAvgPrice > 0 ? ($vendorAvgPrice - $unitPrice) / $vendorAvgPrice : 0.0;
        $discountGap = $expectedDiscount - $actualDiscount;
        
        $features = [
            // === Historical Vendor Pricing (4 features) ===
            'vendor_avg_price' => $vendorAvgPrice,
            'vendor_std_price' => $vendorStdPrice,
            'vendor_min_price' => $vendorMinPrice,
            'vendor_max_price' => $vendorMaxPrice,
            
            // === Market Benchmarks (3 features) ===
            'market_avg_price' => $marketAvgPrice,
            'market_std_price' => $marketStdPrice,
            'category_avg_price' => $categoryAvgPrice,
            
            // === Competitive Analysis (3 features) ===
            'quote_rank' => $quoteRank, // 1=cheapest, 2=second cheapest, etc.
            'cheapest_competitor_quote' => $cheapestQuote,
            'total_quotes_received' => count($competitiveQuotes),
            
            // === Price Velocity & Trends (2 features) ===
            'price_velocity_30d' => $priceVelocity, // % change per day
            'seasonal_price_factor' => $seasonalFactor, // Multiplier (1.2 = 20% seasonal premium)
            
            // === Contract & Volume Discounts (4 features) ===
            'contract_price' => $contractPrice,
            'volume_discount_threshold' => $volumeDiscountThreshold,
            'expected_volume_discount' => $expectedDiscount, // As percentage
            'actual_discount' => $actualDiscount, // As percentage
            
            // === Currency & Geography (2 features) ===
            'currency_volatility_30d' => $currencyVolatility,
            'geographic_price_variance' => $geographicPriceVariance,
            
            // === Payment Terms (1 feature) ===
            'payment_term_price_impact' => $termPriceImpact, // Expected price reduction for extended terms
            
            // === Engineered Statistical Features (7 features) ===
            'vendor_price_zscore' => $vendorPriceZScore,
            'market_price_zscore' => $marketPriceZScore,
            'vendor_price_ratio' => $vendorPriceRatio, // Current / Average
            'market_price_ratio' => $marketPriceRatio,
            'category_price_ratio' => $categoryPriceRatio,
            'contract_deviation_pct' => $contractDeviation,
            'discount_gap' => $discountGap, // Expected - Actual discount
        ];

        $metadata = [
            'entity_type' => 'purchase_order_line_pricing',
            'product_id' => $productId,
            'vendor_id' => $vendorId,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'currency' => $currency,
            'extracted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }

    public function getFeatureKeys(): array
    {
        return [
            // Historical vendor
            'vendor_avg_price',
            'vendor_std_price',
            'vendor_min_price',
            'vendor_max_price',
            
            // Market benchmarks
            'market_avg_price',
            'market_std_price',
            'category_avg_price',
            
            // Competitive
            'quote_rank',
            'cheapest_competitor_quote',
            'total_quotes_received',
            
            // Velocity & trends
            'price_velocity_30d',
            'seasonal_price_factor',
            
            // Contract & discounts
            'contract_price',
            'volume_discount_threshold',
            'expected_volume_discount',
            'actual_discount',
            
            // Currency & geography
            'currency_volatility_30d',
            'geographic_price_variance',
            
            // Payment terms
            'payment_term_price_impact',
            
            // Engineered
            'vendor_price_zscore',
            'market_price_zscore',
            'vendor_price_ratio',
            'market_price_ratio',
            'category_price_ratio',
            'contract_deviation_pct',
            'discount_gap',
        ];
    }

    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }

    /**
     * Calculate quote rank (1 = cheapest)
     * 
     * @param float $currentPrice Price to rank
     * @param array<array{price: float, vendor_id: string}> $quotes All quotes
     * @return int Rank (1-based)
     */
    private function calculateQuoteRank(float $currentPrice, array $quotes): int
    {
        if (count($quotes) === 0) {
            return 0;
        }

        $prices = array_column($quotes, 'price');
        $prices[] = $currentPrice;
        sort($prices);

        $rank = array_search($currentPrice, $prices, true);
        return $rank !== false ? $rank + 1 : 0;
    }
}
