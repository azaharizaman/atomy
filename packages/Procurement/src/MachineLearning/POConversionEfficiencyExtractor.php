<?php

declare(strict_types=1);

namespace Nexus\Procurement\MachineLearning;

use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\Contracts\FeatureSetInterface;
use Nexus\MachineLearning\ValueObjects\FeatureSet;
use Nexus\Procurement\Contracts\ConversionAnalyticsRepositoryInterface;

/**
 * Feature extractor for PO conversion efficiency prediction
 * 
 * Extracts 14 features to predict requisition-to-PO conversion time
 * and identify bottlenecks for resource optimization.
 * 
 * Usage: Conversion time estimation on requisition approval to improve
 * delivery date accuracy and procurement resource planning.
 */
final readonly class POConversionEfficiencyExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';

    public function __construct(
        private ConversionAnalyticsRepositoryInterface $conversionAnalytics
    ) {}

    /**
     * Extract PO conversion efficiency features from approved requisition
     * 
     * @param object $requisition Expected to have vendorId, categoryId, requiresQuote, etc.
     * @return FeatureSetInterface
     */
    public function extract(object $requisition): FeatureSetInterface
    {
        $vendorId = $requisition->vendorId ?? null;
        $categoryId = $requisition->primaryCategoryId ?? '';
        $totalAmount = (float)($requisition->totalAmount ?? 0.0);
        $lineCount = (int)($requisition->lineCount ?? 0);
        $requiresQuote = (bool)($requisition->requiresQuote ?? false);
        $requiresLegalReview = (bool)($requisition->requiresLegalReview ?? false);
        $hasCustomProduct = (bool)($requisition->hasCustomProduct ?? false);
        $isInternationalSourcing = (bool)($requisition->isInternationalSourcing ?? false);
        $specificationCompleteness = (float)($requisition->specificationCompleteness ?? 0.0);
        $budgetPreApproved = (bool)($requisition->budgetPreApproved ?? false);
        
        // Vendor catalog availability
        $vendorInCatalog = $vendorId ? $this->conversionAnalytics->isVendorInCatalog($vendorId) : false;
        $catalogCoverageScore = $vendorId && $vendorInCatalog 
            ? $this->conversionAnalytics->getCatalogCoverageScore($vendorId, $requisition)
            : 0.0;
        
        // Historical conversion time
        $categoryAvgConversion = $this->conversionAnalytics->getCategoryAvgConversionTime($categoryId);
        $vendorAvgConversion = $vendorId 
            ? $this->conversionAnalytics->getVendorAvgConversionTime($vendorId)
            : 0.0;
        
        // Procurement officer capacity
        $assignedOfficerId = $requisition->assignedOfficerId ?? null;
        $officerWorkload = $assignedOfficerId
            ? $this->conversionAnalytics->getProcurementOfficerWorkload($assignedOfficerId)
            : 0;
        $officerAvgProcessingTime = $assignedOfficerId
            ? $this->conversionAnalytics->getOfficerAvgProcessingTime($assignedOfficerId)
            : 0.0;
        
        // Multi-vendor complexity
        $requiresMultiVendor = (bool)($requisition->requiresMultipleVendors ?? false);
        
        // Weekend/holiday offset
        $isWeekendHoliday = $this->isWeekendOrHoliday();
        $businessDaysOffset = $isWeekendHoliday ? 2 : 0;
        
        // Calculate engineered features
        $complexityScore = $this->calculateComplexityScore([
            'requires_quote' => $requiresQuote,
            'requires_legal' => $requiresLegalReview,
            'custom_product' => $hasCustomProduct,
            'international' => $isInternationalSourcing,
            'multi_vendor' => $requiresMultiVendor,
        ]);
        
        $readinessScore = $this->calculateReadinessScore(
            $specificationCompleteness,
            $budgetPreApproved,
            $vendorInCatalog,
            $catalogCoverageScore
        );
        
        $features = [
            // === Vendor Readiness (3 features) ===
            'vendor_in_catalog' => $vendorInCatalog ? 1 : 0,
            'catalog_coverage_score' => $catalogCoverageScore, // 0.0 to 1.0
            'is_preferred_vendor' => $vendorId ? $this->conversionAnalytics->isPreferredVendor($vendorId) : 0,
            
            // === Quote Requirements (1 feature) ===
            'requires_quote' => $requiresQuote ? 1 : 0,
            
            // === Legal Review (1 feature) ===
            'requires_legal_review' => $requiresLegalReview ? 1 : 0,
            
            // === Specification Quality (1 feature) ===
            'specification_completeness' => $specificationCompleteness, // 0.0 to 1.0
            
            // === Historical Performance (2 features) ===
            'category_avg_conversion_days' => $categoryAvgConversion,
            'vendor_avg_conversion_days' => $vendorAvgConversion,
            
            // === Officer Capacity (2 features) ===
            'officer_workload' => $officerWorkload, // Number of active requisitions
            'officer_avg_processing_days' => $officerAvgProcessingTime,
            
            // === Complexity Factors (4 features) ===
            'requires_multi_vendor' => $requiresMultiVendor ? 1 : 0,
            'has_custom_product' => $hasCustomProduct ? 1 : 0,
            'is_international_sourcing' => $isInternationalSourcing ? 1 : 0,
            'business_days_offset' => $businessDaysOffset,
            
            // === Budget Status (1 feature) ===
            'budget_pre_approved' => $budgetPreApproved ? 1 : 0,
            
            // === Engineered Features (3 features) ===
            'complexity_score' => $complexityScore, // 0.0 to 1.0
            'readiness_score' => $readinessScore, // 0.0 to 1.0
            'line_count' => $lineCount,
        ];

        $metadata = [
            'entity_type' => 'requisition_conversion',
            'vendor_id' => $vendorId,
            'category_id' => $categoryId,
            'total_amount' => $totalAmount,
            'assigned_officer_id' => $assignedOfficerId,
            'extracted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }

    public function getFeatureKeys(): array
    {
        return [
            // Vendor readiness
            'vendor_in_catalog',
            'catalog_coverage_score',
            'is_preferred_vendor',
            
            // Quote
            'requires_quote',
            
            // Legal
            'requires_legal_review',
            
            // Specification
            'specification_completeness',
            
            // Historical
            'category_avg_conversion_days',
            'vendor_avg_conversion_days',
            
            // Capacity
            'officer_workload',
            'officer_avg_processing_days',
            
            // Complexity
            'requires_multi_vendor',
            'has_custom_product',
            'is_international_sourcing',
            'business_days_offset',
            
            // Budget
            'budget_pre_approved',
            
            // Engineered
            'complexity_score',
            'readiness_score',
            'line_count',
        ];
    }

    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }

    /**
     * Check if current time is weekend or holiday
     */
    private function isWeekendOrHoliday(): bool
    {
        $now = new \DateTimeImmutable();
        $dayOfWeek = (int)$now->format('N'); // 1=Monday, 7=Sunday
        
        return $dayOfWeek >= 6;
    }

    /**
     * Calculate complexity score based on various factors
     * 
     * @param array<string, bool> $factors Complexity factors
     * @return float Complexity score (0.0 = simple, 1.0 = very complex)
     */
    private function calculateComplexityScore(array $factors): float
    {
        $weights = [
            'requires_quote' => 0.25,
            'requires_legal' => 0.25,
            'custom_product' => 0.20,
            'international' => 0.20,
            'multi_vendor' => 0.10,
        ];

        $score = 0.0;
        foreach ($factors as $key => $value) {
            if ($value) {
                $score += $weights[$key] ?? 0.0;
            }
        }

        return min($score, 1.0);
    }

    /**
     * Calculate readiness score
     * Higher score = faster conversion expected
     * 
     * @param float $specCompleteness Specification completeness (0-1)
     * @param bool $budgetApproved Budget pre-approved flag
     * @param bool $vendorInCatalog Vendor in catalog flag
     * @param float $catalogCoverage Catalog coverage score (0-1)
     * @return float Readiness score (0.0 to 1.0)
     */
    private function calculateReadinessScore(
        float $specCompleteness,
        bool $budgetApproved,
        bool $vendorInCatalog,
        float $catalogCoverage
    ): float {
        $score = 0.0;

        // Specification completeness (40% weight)
        $score += $specCompleteness * 0.4;

        // Budget approval (30% weight)
        $score += ($budgetApproved ? 1.0 : 0.0) * 0.3;

        // Vendor catalog status (30% weight)
        if ($vendorInCatalog) {
            $score += $catalogCoverage * 0.3;
        }

        return min(max($score, 0.0), 1.0);
    }
}
