<?php

declare(strict_types=1);

namespace Nexus\Procurement\Intelligence;

use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\ValueObjects\FeatureSet;
use Nexus\Procurement\Contracts\DeliveryAnalyticsRepositoryInterface;

/**
 * Feature extractor for GRN (Goods Receipt Note) discrepancy prediction
 * 
 * Extracts 18 features to predict likelihood of goods receipt issues
 * before delivery, enabling proactive quality control and vendor management.
 * 
 * Usage: Risk assessment on PO approval and pre-delivery preparation
 * to optimize inspection resources and manage expectations.
 */
final readonly class GRNDiscrepancyPredictionExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';

    public function __construct(
        private DeliveryAnalyticsRepositoryInterface $deliveryAnalytics
    ) {}

    /**
     * Extract GRN discrepancy features from purchase order
     * 
     * @param object $purchaseOrder Expected to have vendorId, items array, shippingMethod, deliveryDate, etc.
     * @return FeatureSetInterface
     */
    public function extract(object $purchaseOrder): FeatureSetInterface
    {
        $vendorId = $purchaseOrder->vendorId ?? '';
        $shippingMethod = $purchaseOrder->shippingMethod ?? 'standard';
        $shippingDistance = (float)($purchaseOrder->shippingDistance ?? 0.0);
        $totalValue = (float)($purchaseOrder->totalValue ?? 0.0);
        $itemCount = (int)($purchaseOrder->itemCount ?? 0);
        $hasFragileGoods = (bool)($purchaseOrder->hasFragileGoods ?? false);
        $hasCustomsRequired = (bool)($purchaseOrder->requiresCustoms ?? false);
        $hasInsurance = (bool)($purchaseOrder->hasInsurance ?? false);
        $deliveryDate = $purchaseOrder->deliveryDate ?? null;
        
        // Vendor delivery performance
        $vendorAccuracy = $this->deliveryAnalytics->getVendorDeliveryAccuracy($vendorId);
        $vendorDamageRate = $this->deliveryAnalytics->getVendorDamageRate($vendorId);
        $vendorDefectRate = $this->deliveryAnalytics->getVendorDefectRate($vendorId);
        $vendorPartialDeliveryFreq = $this->deliveryAnalytics->getVendorPartialDeliveryFrequency($vendorId);
        
        // Lead time reliability
        $leadTimeVariance = $this->deliveryAnalytics->getVendorLeadTimeVariance($vendorId);
        $avgLeadTime = $this->deliveryAnalytics->getVendorAverageLeadTime($vendorId);
        $promisedLeadTime = (int)($purchaseOrder->promisedLeadTime ?? 0);
        
        // Quality metrics
        $qualityInspectionFailRate = $this->deliveryAnalytics->getVendorQualityFailRate($vendorId);
        $packagingAdequacy = $this->deliveryAnalytics->getVendorPackagingScore($vendorId);
        
        // Product-specific risks
        $productCategories = $purchaseOrder->productCategories ?? [];
        $maxCategoryRisk = $this->getMaxCategoryRisk($productCategories);
        
        // Seasonal and environmental factors
        $seasonalDemandSpike = $this->deliveryAnalytics->isSeasonalDemandPeak();
        
        // Transit risk
        $transitRiskScore = $this->calculateTransitRisk($shippingDistance, $shippingMethod, $hasFragileGoods);
        
        // Vendor responsiveness
        $issueResponseTime = $this->deliveryAnalytics->getVendorIssueResponseTime($vendorId);
        $vendorCommunicationScore = $this->deliveryAnalytics->getVendorCommunicationScore($vendorId);
        
        // Insurance and claims
        $insuranceClaimHistory = $this->deliveryAnalytics->getVendorInsuranceClaimCount($vendorId);
        
        // Complexity factors
        $customsComplexity = $hasCustomsRequired ? $this->deliveryAnalytics->getCustomsComplexityScore($vendorId) : 0.0;
        
        // Calculate engineered features
        $leadTimeDeviation = $promisedLeadTime > 0 && $avgLeadTime > 0
            ? abs($promisedLeadTime - $avgLeadTime) / $avgLeadTime
            : 0.0;
        
        $overallRiskScore = $this->calculateOverallRiskScore([
            'accuracy' => 1.0 - $vendorAccuracy,
            'damage' => $vendorDamageRate,
            'defect' => $vendorDefectRate,
            'quality_fail' => $qualityInspectionFailRate,
            'transit' => $transitRiskScore,
        ]);
        
        $features = [
            // === Vendor Delivery Performance (4 features) ===
            'vendor_delivery_accuracy' => $vendorAccuracy, // 0.0 to 1.0
            'vendor_damage_rate' => $vendorDamageRate, // 0.0 to 1.0
            'vendor_defect_rate' => $vendorDefectRate, // 0.0 to 1.0
            'vendor_partial_delivery_freq' => $vendorPartialDeliveryFreq, // 0.0 to 1.0
            
            // === Lead Time Reliability (3 features) ===
            'vendor_lead_time_variance' => $leadTimeVariance, // Days std deviation
            'vendor_avg_lead_time' => $avgLeadTime, // Days
            'promised_lead_time' => (float)$promisedLeadTime,
            
            // === Quality Metrics (2 features) ===
            'quality_inspection_fail_rate' => $qualityInspectionFailRate,
            'packaging_adequacy_score' => $packagingAdequacy, // 0.0 to 1.0
            
            // === Product & Category Risk (1 feature) ===
            'max_product_category_risk' => $maxCategoryRisk, // 0.0 to 1.0
            
            // === Seasonal & Environmental (1 feature) ===
            'seasonal_demand_spike' => $seasonalDemandSpike ? 1 : 0,
            
            // === Shipping & Transit (2 features) ===
            'shipping_distance_km' => $shippingDistance,
            'transit_risk_score' => $transitRiskScore, // 0.0 to 1.0
            
            // === Vendor Responsiveness (2 features) ===
            'issue_response_time_hours' => $issueResponseTime,
            'vendor_communication_score' => $vendorCommunicationScore, // 0.0 to 1.0
            
            // === Insurance & Claims (1 feature) ===
            'insurance_claim_count' => $insuranceClaimHistory,
            
            // === Complexity Factors (2 features) ===
            'customs_complexity_score' => $customsComplexity, // 0.0 to 1.0
            'has_fragile_goods' => $hasFragileGoods ? 1 : 0,
            
            // === Engineered Features (3 features) ===
            'lead_time_deviation_pct' => $leadTimeDeviation,
            'overall_risk_score' => $overallRiskScore, // Composite 0.0 to 1.0
            'value_per_item' => $itemCount > 0 ? $totalValue / $itemCount : 0.0,
        ];

        $metadata = [
            'entity_type' => 'purchase_order_grn_risk',
            'vendor_id' => $vendorId,
            'shipping_method' => $shippingMethod,
            'total_value' => $totalValue,
            'item_count' => $itemCount,
            'delivery_date' => $deliveryDate,
            'extracted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }

    public function getFeatureKeys(): array
    {
        return [
            // Vendor performance
            'vendor_delivery_accuracy',
            'vendor_damage_rate',
            'vendor_defect_rate',
            'vendor_partial_delivery_freq',
            
            // Lead time
            'vendor_lead_time_variance',
            'vendor_avg_lead_time',
            'promised_lead_time',
            
            // Quality
            'quality_inspection_fail_rate',
            'packaging_adequacy_score',
            
            // Product risk
            'max_product_category_risk',
            
            // Seasonal
            'seasonal_demand_spike',
            
            // Transit
            'shipping_distance_km',
            'transit_risk_score',
            
            // Responsiveness
            'issue_response_time_hours',
            'vendor_communication_score',
            
            // Insurance
            'insurance_claim_count',
            
            // Complexity
            'customs_complexity_score',
            'has_fragile_goods',
            
            // Engineered
            'lead_time_deviation_pct',
            'overall_risk_score',
            'value_per_item',
        ];
    }

    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }

    /**
     * Get maximum risk score across product categories
     * 
     * @param array<string> $categoryIds Product category identifiers
     * @return float Maximum risk score
     */
    private function getMaxCategoryRisk(array $categoryIds): float
    {
        if (empty($categoryIds)) {
            return 0.0;
        }

        $risks = array_map(
            fn($catId) => $this->deliveryAnalytics->getProductCategoryRiskScore($catId),
            $categoryIds
        );

        return max($risks);
    }

    /**
     * Calculate transit risk score
     * 
     * @param float $distance Shipping distance in km
     * @param string $method Shipping method
     * @param bool $fragile Has fragile goods
     * @return float Risk score (0.0 to 1.0)
     */
    private function calculateTransitRisk(float $distance, string $method, bool $fragile): float
    {
        $baseRisk = 0.0;

        // Distance factor (normalized to 0-1, assumes max 5000km)
        $distanceRisk = min($distance / 5000.0, 1.0);

        // Method factor
        $methodRisk = match($method) {
            'air' => 0.1,
            'express' => 0.2,
            'standard' => 0.3,
            'economy' => 0.5,
            'sea' => 0.4,
            default => 0.3,
        };

        // Fragile penalty
        $fragileRisk = $fragile ? 0.3 : 0.0;

        // Weighted combination
        $baseRisk = (0.4 * $distanceRisk) + (0.4 * $methodRisk) + (0.2 * $fragileRisk);

        return min(max($baseRisk, 0.0), 1.0);
    }

    /**
     * Calculate overall composite risk score
     * 
     * @param array<string, float> $riskFactors Individual risk scores
     * @return float Composite score (0.0 to 1.0)
     */
    private function calculateOverallRiskScore(array $riskFactors): float
    {
        if (empty($riskFactors)) {
            return 0.0;
        }

        // Weighted average of risk factors
        $weights = [
            'accuracy' => 0.25,
            'damage' => 0.20,
            'defect' => 0.20,
            'quality_fail' => 0.20,
            'transit' => 0.15,
        ];

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($riskFactors as $key => $value) {
            $weight = $weights[$key] ?? 0.0;
            $weightedSum += $value * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;
    }
}
