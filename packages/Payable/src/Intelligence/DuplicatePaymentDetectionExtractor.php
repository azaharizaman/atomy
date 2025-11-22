<?php

declare(strict_types=1);

namespace Nexus\Payable\Intelligence;

use DateTimeImmutable;
use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\ValueObjects\FeatureSet;
use Nexus\Payable\Contracts\VendorPaymentAnalyticsRepositoryInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Setting\Contracts\SettingsManagerInterface;

/**
 * Duplicate payment detection extractor
 * 
 * Extracts 22 features to detect duplicate vendor payments before transaction commit.
 * Uses invoice similarity, vendor patterns, 3-way match anomalies, and behavioral flags.
 * 
 * Schema v1.0 - Initial implementation
 */
final readonly class DuplicatePaymentDetectionExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';
    
    public function __construct(
        private VendorPaymentAnalyticsRepositoryInterface $analytics,
        private PeriodManagerInterface $periodManager,
        private SettingsManagerInterface $settings
    ) {}
    
    /**
     * {@inheritDoc}
     */
    public function extract(object $entity): FeatureSetInterface
    {
        // Expected entity structure: {vendor_id, invoice_number, amount, invoice_date, ...}
        $vendorId = $entity->vendor_id ?? throw new \InvalidArgumentException('Missing vendor_id');
        $invoiceNumber = $entity->invoice_number ?? '';
        $amount = (float) ($entity->amount ?? 0.0);
        $invoiceDate = $entity->invoice_date ?? new DateTimeImmutable();
        
        // Get recent bills for similarity calculation
        $recentBills = $this->analytics->getRecentBillsByVendor($vendorId, 30);
        
        $features = [
            // Invoice Similarity Features (5)
            'invoice_number_similarity_score' => $this->calculateInvoiceNumberSimilarity($invoiceNumber, $recentBills),
            'invoice_amount_exact_match_count' => $this->countExactAmountMatches($amount, $recentBills),
            'invoice_date_proximity_days' => $this->calculateDateProximity($invoiceDate, $recentBills),
            'vendor_invoice_sequence_gap' => (float) $this->analytics->getInvoiceSequenceGaps($vendorId),
            
            // Vendor Pattern Features (6)
            'vendor_avg_invoice_amount' => $this->analytics->getAverageInvoiceAmount($vendorId),
            'vendor_invoice_frequency_days' => $this->analytics->getInvoiceFrequencyDays($vendorId),
            'vendor_duplicate_history_count' => (float) $this->analytics->getDuplicateHistoryCount($vendorId),
            'vendor_split_invoice_pattern' => $this->analytics->hasSplitInvoicingPattern($vendorId) ? 1.0 : 0.0,
            'vendor_amount_stddev' => $this->analytics->getInvoiceAmountStdDev($vendorId),
            
            // Payment History Features (3)
            'payment_to_same_vendor_last_7days' => (float) $this->analytics->getPaymentCountLastNDays($vendorId, 7),
            'payment_amount_round_number_flag' => $this->isRoundNumber($amount) ? 1.0 : 0.0,
            'days_since_last_vendor_payment' => $this->calculateDaysSinceLastPayment($vendorId),
            
            // 3-Way Match Anomalies (2)
            'po_line_already_invoiced_pct' => $this->calculatePoAlreadyInvoicedPct($entity),
            'grn_quantity_variance' => $this->calculateGrnVariance($entity),
            
            // Behavioral Flags (4)
            'after_hours_submission_flag' => $this->isAfterHours() ? 1.0 : 0.0,
            'fiscal_period_end_proximity' => (float) $this->getDaysUntilPeriodClose(),
            'user_approval_bypass_count' => $this->getUserBypassCount($entity),
            'rush_payment_flag' => ($entity->rush_payment ?? false) ? 1.0 : 0.0,
            
            // Engineered Features (2)
            'duplicate_probability_score' => 0.0, // Calculated below
            'fraud_risk_score' => 0.0, // Calculated below
        ];
        
        // Calculate composite scores
        $features['duplicate_probability_score'] = $this->calculateDuplicateProbability($features);
        $features['fraud_risk_score'] = $this->calculateFraudRisk($features);
        
        $metadata = [
            'entity_type' => 'vendor_bill',
            'vendor_id' => $vendorId,
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'extracted_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }
    
    /**
     * {@inheritDoc}
     */
    public function getFeatureKeys(): array
    {
        return [
            'invoice_number_similarity_score',
            'invoice_amount_exact_match_count',
            'invoice_date_proximity_days',
            'vendor_invoice_sequence_gap',
            'vendor_avg_invoice_amount',
            'vendor_invoice_frequency_days',
            'vendor_duplicate_history_count',
            'vendor_split_invoice_pattern',
            'vendor_amount_stddev',
            'payment_to_same_vendor_last_7days',
            'payment_amount_round_number_flag',
            'days_since_last_vendor_payment',
            'po_line_already_invoiced_pct',
            'grn_quantity_variance',
            'after_hours_submission_flag',
            'fiscal_period_end_proximity',
            'user_approval_bypass_count',
            'rush_payment_flag',
            'duplicate_probability_score',
            'fraud_risk_score',
        ];
    }
    
    /**
     * {@inheritDoc}
     */
    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }
    
    /**
     * Calculate invoice number similarity using Levenshtein distance
     * 
     * @param string $invoiceNumber Current invoice number
     * @param array<array> $recentBills Recent bills for comparison
     * @return float Similarity score 0.0-1.0 (1.0 = exact match)
     */
    private function calculateInvoiceNumberSimilarity(string $invoiceNumber, array $recentBills): float
    {
        if (empty($invoiceNumber) || empty($recentBills)) {
            return 0.0;
        }
        
        $maxSimilarity = 0.0;
        
        foreach ($recentBills as $bill) {
            $existingNumber = $bill['invoice_number'] ?? '';
            if (empty($existingNumber)) {
                continue;
            }
            
            // Calculate Levenshtein distance
            $distance = levenshtein($invoiceNumber, $existingNumber);
            $maxLength = max(strlen($invoiceNumber), strlen($existingNumber));
            
            // Convert distance to similarity (0-1 scale)
            $similarity = $maxLength > 0 ? 1.0 - ($distance / $maxLength) : 0.0;
            $maxSimilarity = max($maxSimilarity, $similarity);
        }
        
        return $maxSimilarity;
    }
    
    /**
     * Count exact amount matches in recent bills
     * 
     * @param float $amount Current bill amount
     * @param array<array> $recentBills Recent bills
     * @return float Count of exact matches (with $0.01 tolerance)
     */
    private function countExactAmountMatches(float $amount, array $recentBills): float
    {
        $count = 0;
        $tolerance = 0.01;
        
        foreach ($recentBills as $bill) {
            $billAmount = (float) ($bill['amount'] ?? 0.0);
            if (abs($amount - $billAmount) <= $tolerance) {
                $count++;
            }
        }
        
        return (float) $count;
    }
    
    /**
     * Calculate minimum date proximity to recent bills
     * 
     * @param DateTimeImmutable $invoiceDate Current invoice date
     * @param array<array> $recentBills Recent bills
     * @return float Minimum days to nearest bill
     */
    private function calculateDateProximity(DateTimeImmutable $invoiceDate, array $recentBills): float
    {
        if (empty($recentBills)) {
            return 999.0;
        }
        
        $minDays = 999.0;
        
        foreach ($recentBills as $bill) {
            $billDate = $bill['bill_date'] ?? null;
            if ($billDate instanceof DateTimeImmutable) {
                $diff = abs($invoiceDate->diff($billDate)->days);
                $minDays = min($minDays, $diff);
            }
        }
        
        return $minDays;
    }
    
    /**
     * Calculate days since last vendor payment
     * 
     * @param string $vendorId Vendor identifier
     * @return float Days since last payment, 999.0 if never paid
     */
    private function calculateDaysSinceLastPayment(string $vendorId): float
    {
        $lastPayment = $this->analytics->getLastPaymentDate($vendorId);
        
        if ($lastPayment === null) {
            return 999.0;
        }
        
        $now = new DateTimeImmutable();
        return (float) $now->diff($lastPayment)->days;
    }
    
    /**
     * Check if amount is suspiciously round number
     * 
     * @param float $amount Invoice amount
     * @return bool True if round number
     */
    private function isRoundNumber(float $amount): bool
    {
        // Check if divisible by 1000, 500, or 100
        return ($amount > 0 && (
            fmod($amount, 1000.0) === 0.0 ||
            fmod($amount, 500.0) === 0.0 ||
            fmod($amount, 100.0) === 0.0
        ));
    }
    
    /**
     * Calculate percentage of PO already invoiced
     * 
     * @param object $entity Bill entity
     * @return float Percentage 0.0-100.0
     */
    private function calculatePoAlreadyInvoicedPct(object $entity): float
    {
        // This would query PO lines vs. existing bills
        // For now, return placeholder
        return $entity->po_already_invoiced_pct ?? 0.0;
    }
    
    /**
     * Calculate GRN quantity variance
     * 
     * @param object $entity Bill entity
     * @return float Variance percentage
     */
    private function calculateGrnVariance(object $entity): float
    {
        // This would compare bill quantity to GRN quantity
        return $entity->grn_variance ?? 0.0;
    }
    
    /**
     * Check if submission is after business hours
     * 
     * @return bool True if after hours
     */
    private function isAfterHours(): bool
    {
        $hour = (int) (new DateTimeImmutable())->format('H');
        return $hour < 6 || $hour > 18;
    }
    
    /**
     * Get days until current fiscal period closes
     * 
     * @return int Days until close
     */
    private function getDaysUntilPeriodClose(): int
    {
        try {
            $currentPeriod = $this->periodManager->getCurrentPeriod();
            $now = new DateTimeImmutable();
            $endDate = $currentPeriod->getEndDate();
            
            if ($endDate < $now) {
                return 0;
            }
            
            return $now->diff($endDate)->days;
        } catch (\Throwable $e) {
            return 999;
        }
    }
    
    /**
     * Get user approval bypass count
     * 
     * @param object $entity Bill entity
     * @return float Bypass count
     */
    private function getUserBypassCount(object $entity): float
    {
        return (float) ($entity->approval_bypass_count ?? 0);
    }
    
    /**
     * Calculate composite duplicate probability score
     * 
     * @param array<string, float> $features Extracted features
     * @return float Probability score 0.0-1.0
     */
    private function calculateDuplicateProbability(array $features): float
    {
        // Weighted composite: similarity + exact matches + date proximity
        $similarity = $features['invoice_number_similarity_score'] * 0.4;
        $exactMatches = min($features['invoice_amount_exact_match_count'] / 5.0, 1.0) * 0.3;
        $dateProximity = ($features['invoice_date_proximity_days'] < 7) ? 0.3 : 0.0;
        
        return min($similarity + $exactMatches + $dateProximity, 1.0);
    }
    
    /**
     * Calculate composite fraud risk score
     * 
     * @param array<string, float> $features Extracted features
     * @return float Risk score 0.0-1.0
     */
    private function calculateFraudRisk(array $features): float
    {
        // Weighted composite: after hours + bypass + split pattern + round number
        $afterHours = $features['after_hours_submission_flag'] * 0.25;
        $bypass = min($features['user_approval_bypass_count'] / 3.0, 1.0) * 0.25;
        $splitPattern = $features['vendor_split_invoice_pattern'] * 0.25;
        $roundNumber = $features['payment_amount_round_number_flag'] * 0.25;
        
        return min($afterHours + $bypass + $splitPattern + $roundNumber, 1.0);
    }
}
