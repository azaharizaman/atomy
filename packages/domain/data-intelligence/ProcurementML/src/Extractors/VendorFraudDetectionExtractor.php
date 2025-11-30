<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Extractors;

use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\Contracts\FeatureSetInterface;
use Nexus\MachineLearning\ValueObjects\FeatureSet;
use Nexus\ProcurementML\Contracts\VendorAnalyticsRepositoryInterface;

/**
 * Feature extractor for vendor fraud detection
 * 
 * Extracts 25 features to identify suspicious vendor behavior patterns
 * including duplicate vendors, pricing anomalies, relationship red flags,
 * and document inconsistencies.
 * 
 * Usage: Real-time fraud screening on PO creation, vendor modification,
 * and payment request events.
 */
final readonly class VendorFraudDetectionExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';

    public function __construct(
        private VendorAnalyticsRepositoryInterface $vendorAnalytics
    ) {}

    /**
     * Extract fraud detection features from vendor transaction
     * 
     * @param object $transaction Expected to have vendorId, productId, unitPrice, totalAmount, requesterId, etc.
     * @return FeatureSetInterface
     */
    public function extract(object $transaction): FeatureSetInterface
    {
        $vendorId = $transaction->vendorId ?? '';
        $productId = $transaction->productId ?? '';
        $unitPrice = (float)($transaction->unitPrice ?? 0.0);
        $totalAmount = (float)($transaction->totalAmount ?? 0.0);
        $requesterId = $transaction->requesterId ?? '';
        
        // Duplicate vendor pattern detection
        $duplicates = $this->vendorAnalytics->findSimilarVendors($vendorId);
        $bankDuplicates = $this->vendorAnalytics->findVendorsWithSameBankAccount($vendorId);
        $contactDuplicates = $this->vendorAnalytics->findVendorsWithSameContact($vendorId);
        
        // Behavioral metrics
        $priceHistory = $this->vendorAnalytics->getVendorPriceHistory($vendorId, $productId);
        $winRate = $this->vendorAnalytics->getRfqWinRate($vendorId);
        $budgetProximity = $this->vendorAnalytics->getAverageBudgetProximity($vendorId);
        
        // Relationship analysis
        $requesterVendorFreq = $this->vendorAnalytics->getRequesterVendorFrequency($requesterId, $vendorId);
        $afterHoursCount = $this->vendorAnalytics->getAfterHoursSubmissionCount($requesterId, $vendorId);
        $splitOrderCount = $this->vendorAnalytics->getSuspiciousSplitOrderCount($vendorId);
        
        // Document integrity
        $missingDocs = $this->vendorAnalytics->getMissingCertificationCount($vendorId);
        $invoiceGaps = $this->vendorAnalytics->getInvoiceNumberGapCount($vendorId);
        
        // Calculate engineered features
        $nameSimilarityScore = count($duplicates) > 0 ? max(array_column($duplicates, 'similarity_score')) : 0.0;
        $priceIncreaseRate = $this->calculatePriceIncreaseRate($priceHistory);
        $priceVolatility = $this->calculatePriceVolatility($priceHistory);
        
        $features = [
            // === Duplicate Vendor Patterns (5 features) ===
            'similar_vendor_count' => count($duplicates),
            'max_name_similarity_score' => $nameSimilarityScore,
            'same_bank_account_count' => count($bankDuplicates),
            'same_contact_count' => count($contactDuplicates),
            'has_duplicate_indicators' => (count($duplicates) > 0 || count($bankDuplicates) > 0 || count($contactDuplicates) > 0) ? 1 : 0,
            
            // === Behavioral Anomalies (7 features) ===
            'price_increase_rate_6m' => $priceIncreaseRate, // % increase over 6 months
            'price_volatility_coefficient' => $priceVolatility, // Coefficient of variation
            'rfq_win_rate' => $winRate, // Unusually high win rate
            'avg_budget_proximity' => $budgetProximity, // Always prices near budget limit
            'payment_term_changes_count' => $this->vendorAnalytics->getPaymentTermChangeCount($vendorId),
            'rush_order_frequency' => $this->vendorAnalytics->getRushOrderFrequency($vendorId),
            'price_consistency_score' => $this->calculatePriceConsistency($priceHistory),
            
            // === Relationship Red Flags (6 features) ===
            'requester_vendor_frequency' => $requesterVendorFreq, // Same requester always uses this vendor
            'after_hours_submission_count' => $afterHoursCount,
            'approval_bypass_attempts' => $this->vendorAnalytics->getApprovalBypassAttempts($vendorId),
            'threshold_split_order_count' => $splitOrderCount, // Orders split to avoid approval thresholds
            'exclusive_requester_flag' => ($requesterVendorFreq > 0.8) ? 1 : 0, // >80% of orders from one requester
            'unusual_requester_vendor_pairing' => $this->vendorAnalytics->isUnusualRequesterVendorPairing($requesterId, $vendorId) ? 1 : 0,
            
            // === Document Anomalies (4 features) ===
            'missing_certification_count' => $missingDocs,
            'invoice_number_gap_count' => $invoiceGaps, // Gaps in sequential invoice numbers
            'document_metadata_anomaly_score' => $this->vendorAnalytics->getDocumentMetadataAnomalyScore($vendorId),
            'registration_verification_failed' => !$this->vendorAnalytics->isRegistrationVerified($vendorId) ? 1 : 0,
            
            // === Transaction Context (3 features) ===
            'current_unit_price' => $unitPrice,
            'current_total_amount' => $totalAmount,
            'vendor_lifetime_value' => $this->vendorAnalytics->getVendorLifetimeValue($vendorId),
        ];

        $metadata = [
            'entity_type' => 'purchase_order_vendor_check',
            'vendor_id' => $vendorId,
            'product_id' => $productId,
            'requester_id' => $requesterId,
            'extracted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }

    public function getFeatureKeys(): array
    {
        return [
            // Duplicate patterns
            'similar_vendor_count',
            'max_name_similarity_score',
            'same_bank_account_count',
            'same_contact_count',
            'has_duplicate_indicators',
            
            // Behavioral
            'price_increase_rate_6m',
            'price_volatility_coefficient',
            'rfq_win_rate',
            'avg_budget_proximity',
            'payment_term_changes_count',
            'rush_order_frequency',
            'price_consistency_score',
            
            // Relationship
            'requester_vendor_frequency',
            'after_hours_submission_count',
            'approval_bypass_attempts',
            'threshold_split_order_count',
            'exclusive_requester_flag',
            'unusual_requester_vendor_pairing',
            
            // Document
            'missing_certification_count',
            'invoice_number_gap_count',
            'document_metadata_anomaly_score',
            'registration_verification_failed',
            
            // Context
            'current_unit_price',
            'current_total_amount',
            'vendor_lifetime_value',
        ];
    }

    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }

    /**
     * Calculate price increase rate over last 6 months
     * 
     * @param array<array{price: float, date: string}> $priceHistory Price history records
     * @return float Percentage increase (0.15 = 15% increase)
     */
    private function calculatePriceIncreaseRate(array $priceHistory): float
    {
        if (count($priceHistory) < 2) {
            return 0.0;
        }

        // Sort by date
        usort($priceHistory, fn($a, $b) => $a['date'] <=> $b['date']);

        $firstPrice = $priceHistory[0]['price'] ?? 0.0;
        $lastPrice = $priceHistory[count($priceHistory) - 1]['price'] ?? 0.0;

        if ($firstPrice <= 0) {
            return 0.0;
        }

        return ($lastPrice - $firstPrice) / $firstPrice;
    }

    /**
     * Calculate price volatility (coefficient of variation)
     * 
     * @param array<array{price: float, date: string}> $priceHistory Price history records
     * @return float Coefficient of variation (0.0 = no variation, >0.5 = high variation)
     */
    private function calculatePriceVolatility(array $priceHistory): float
    {
        $prices = array_column($priceHistory, 'price');
        
        if (count($prices) < 2) {
            return 0.0;
        }

        $mean = array_sum($prices) / count($prices);
        
        if ($mean <= 0) {
            return 0.0;
        }

        $variance = 0.0;
        foreach ($prices as $price) {
            $variance += ($price - $mean) ** 2;
        }
        $variance /= count($prices);
        $stdDev = sqrt($variance);

        return $stdDev / $mean; // Coefficient of variation
    }

    /**
     * Calculate price consistency score
     * Higher score = more consistent pricing (less suspicious)
     * 
     * @param array<array{price: float, date: string}> $priceHistory Price history records
     * @return float Consistency score (0.0 to 1.0)
     */
    private function calculatePriceConsistency(array $priceHistory): float
    {
        $volatility = $this->calculatePriceVolatility($priceHistory);
        
        // Convert volatility to consistency (inverse relationship)
        // CV of 0 = 1.0 consistency, CV of 0.5+ = 0 consistency
        return max(0.0, 1.0 - ($volatility * 2));
    }
}
