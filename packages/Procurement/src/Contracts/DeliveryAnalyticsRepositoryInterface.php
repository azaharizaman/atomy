<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Delivery analytics repository interface for GRN discrepancy prediction
 * 
 * Provides delivery quality and reliability metrics for vendor
 * performance analysis and risk assessment.
 */
interface DeliveryAnalyticsRepositoryInterface
{
    /**
     * Get vendor's delivery accuracy rate
     * 
     * @param string $vendorId Vendor identifier
     * @return float Accuracy rate (0.0 to 1.0, e.g., 0.95 = 95% accurate deliveries)
     */
    public function getVendorDeliveryAccuracy(string $vendorId): float;

    /**
     * Get vendor's damage rate
     * 
     * @param string $vendorId Vendor identifier
     * @return float Damage rate (0.0 to 1.0, percentage of deliveries with damage)
     */
    public function getVendorDamageRate(string $vendorId): float;

    /**
     * Get vendor's defect rate
     * 
     * @param string $vendorId Vendor identifier
     * @return float Defect rate (0.0 to 1.0, percentage of items with defects)
     */
    public function getVendorDefectRate(string $vendorId): float;

    /**
     * Get vendor's partial delivery frequency
     * 
     * @param string $vendorId Vendor identifier
     * @return float Frequency (0.0 to 1.0, percentage of partial vs complete deliveries)
     */
    public function getVendorPartialDeliveryFrequency(string $vendorId): float;

    /**
     * Get vendor's lead time variance
     * 
     * @param string $vendorId Vendor identifier
     * @return float Standard deviation in days
     */
    public function getVendorLeadTimeVariance(string $vendorId): float;

    /**
     * Get vendor's average lead time
     * 
     * @param string $vendorId Vendor identifier
     * @return float Average lead time in days
     */
    public function getVendorAverageLeadTime(string $vendorId): float;

    /**
     * Get vendor's quality inspection fail rate
     * 
     * @param string $vendorId Vendor identifier
     * @return float Fail rate (0.0 to 1.0)
     */
    public function getVendorQualityFailRate(string $vendorId): float;

    /**
     * Get vendor's packaging adequacy score
     * Based on historical packaging quality assessments
     * 
     * @param string $vendorId Vendor identifier
     * @return float Score (0.0 = poor, 1.0 = excellent)
     */
    public function getVendorPackagingScore(string $vendorId): float;

    /**
     * Check if current period is seasonal demand peak
     * 
     * @return bool True if peak season
     */
    public function isSeasonalDemandPeak(): bool;

    /**
     * Get vendor's issue response time
     * 
     * @param string $vendorId Vendor identifier
     * @return float Average hours to respond to issues
     */
    public function getVendorIssueResponseTime(string $vendorId): float;

    /**
     * Get vendor's communication score
     * Based on responsiveness, clarity, proactive updates
     * 
     * @param string $vendorId Vendor identifier
     * @return float Score (0.0 = poor, 1.0 = excellent)
     */
    public function getVendorCommunicationScore(string $vendorId): float;

    /**
     * Get vendor's insurance claim count
     * 
     * @param string $vendorId Vendor identifier
     * @return int Total number of insurance claims filed
     */
    public function getVendorInsuranceClaimCount(string $vendorId): int;

    /**
     * Get customs/import complexity score for vendor
     * 
     * @param string $vendorId Vendor identifier
     * @return float Complexity score (0.0 = simple, 1.0 = very complex)
     */
    public function getCustomsComplexityScore(string $vendorId): float;

    /**
     * Get product category risk score
     * 
     * @param string $categoryId Product category identifier
     * @return float Risk score (0.0 = low risk, 1.0 = high risk)
     */
    public function getProductCategoryRiskScore(string $categoryId): float;
}
