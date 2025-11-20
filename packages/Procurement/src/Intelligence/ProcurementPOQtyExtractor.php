<?php

declare(strict_types=1);

namespace Nexus\Procurement\Intelligence;

use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\ValueObjects\FeatureSet;
use Nexus\Procurement\Contracts\HistoricalDataRepositoryInterface;

/**
 * Procurement PO quantity feature extractor
 * 
 * Extracts features from purchase order lines for anomaly detection.
 * Schema Version: 1.0
 */
final readonly class ProcurementPOQtyExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';

    public function __construct(
        private HistoricalDataRepositoryInterface $historicalRepo
    ) {}

    /**
     * Extract features from PO line
     * 
     * @param object $poLine Purchase order line entity
     * @return FeatureSetInterface
     */
    public function extract(object $poLine): FeatureSetInterface
    {
        // Extract current transaction data
        $currentQty = (float) $poLine->getQuantity()->getValue();
        $currentPrice = (float) $poLine->getUnitPrice()->getAmount();
        $lineTotal = $currentQty * $currentPrice;
        
        // Fetch historical data
        $productId = $poLine->getProductVariantId();
        $vendorId = $poLine->getVendorPartyId();
        
        $avgQty = $this->historicalRepo->getAverageQty($productId);
        $stdQty = $this->historicalRepo->getStdQty($productId);
        $avgPrice = $this->historicalRepo->getAveragePrice($productId);
        $stdPrice = $this->historicalRepo->getStdPrice($productId);
        $vendorAvgQty = $this->historicalRepo->getVendorAverageQty($productId, $vendorId);
        $vendorTransactionCount = $this->historicalRepo->getTransactionCountByVendor($vendorId);
        $daysSinceLastOrder = $this->historicalRepo->getDaysSinceLastOrder($productId);
        
        // Calculate engineered features
        $qtyDelta = $currentQty - $avgQty;
        $qtyRatio = $avgQty > 0 ? $currentQty / $avgQty : 1.0;
        $qtyZScore = $stdQty > 0 ? $qtyDelta / $stdQty : 0.0;
        
        $priceDelta = $currentPrice - $avgPrice;
        $priceRatio = $avgPrice > 0 ? $currentPrice / $avgPrice : 1.0;
        
        // Boolean flags
        $isNewProduct = $avgQty === 0.0;
        $isFirstOrderWithVendor = $vendorTransactionCount === 0;
        $isSeasonalSpike = $daysSinceLastOrder > 90 && $qtyRatio > 2.0;
        $isBulkDiscountThreshold = $currentQty >= 100;
        
        return new FeatureSet(
            features: [
                // Core transaction features
                'quantity_ordered' => $currentQty,
                'unit_price' => $currentPrice,
                'line_total' => $lineTotal,
                
                // Historical averages
                'historical_avg_qty' => $avgQty,
                'historical_std_qty' => $stdQty,
                'historical_avg_price' => $avgPrice,
                'historical_std_price' => $stdPrice,
                
                // Engineered statistical features
                'qty_delta_from_avg' => $qtyDelta,
                'qty_ratio_to_avg' => $qtyRatio,
                'qty_zscore' => $qtyZScore,
                'price_delta_from_avg' => $priceDelta,
                'price_ratio_to_avg' => $priceRatio,
                
                // Vendor features
                'vendor_transaction_count' => $vendorTransactionCount,
                'vendor_avg_qty' => $vendorAvgQty,
                
                // Categorical features
                'product_category_id' => $poLine->getProductCategory()->getId(),
                
                // Temporal features
                'days_since_last_order' => $daysSinceLastOrder,
                
                // Boolean flags
                'is_new_product' => $isNewProduct,
                'is_first_order_with_vendor' => $isFirstOrderWithVendor,
                'is_seasonal_spike' => $isSeasonalSpike,
                'is_bulk_discount_threshold' => $isBulkDiscountThreshold,
            ],
            schemaVersion: self::SCHEMA_VERSION,
            metadata: [
                'entity_type' => 'purchase_order_line',
                'product_id' => $productId,
                'vendor_id' => $vendorId,
                'extracted_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public function getFeatureKeys(): array
    {
        return [
            'quantity_ordered',
            'unit_price',
            'line_total',
            'historical_avg_qty',
            'historical_std_qty',
            'historical_avg_price',
            'historical_std_price',
            'qty_delta_from_avg',
            'qty_ratio_to_avg',
            'qty_zscore',
            'price_delta_from_avg',
            'price_ratio_to_avg',
            'vendor_transaction_count',
            'vendor_avg_qty',
            'product_category_id',
            'days_since_last_order',
            'is_new_product',
            'is_first_order_with_vendor',
            'is_seasonal_spike',
            'is_bulk_discount_threshold',
        ];
    }

    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }
}
