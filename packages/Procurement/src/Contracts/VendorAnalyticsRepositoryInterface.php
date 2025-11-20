<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Vendor analytics repository interface for fraud detection
 * 
 * Provides complex analytical queries for vendor behavior analysis.
 */
interface VendorAnalyticsRepositoryInterface
{
    /**
     * Find vendors with similar names (fuzzy matching)
     * 
     * @param string $vendorId Vendor to check
     * @return array<array{vendor_id: string, similarity_score: float}> Similar vendors with Levenshtein-based scores
     */
    public function findSimilarVendors(string $vendorId): array;

    /**
     * Find vendors sharing the same bank account
     * 
     * @param string $vendorId Vendor to check
     * @return array<array{vendor_id: string, bank_account: string}> Vendors with same account
     */
    public function findVendorsWithSameBankAccount(string $vendorId): array;

    /**
     * Find vendors sharing contact information (phone/email/address)
     * 
     * @param string $vendorId Vendor to check
     * @return array<array{vendor_id: string, match_type: string}> Vendors with same contact info
     */
    public function findVendorsWithSameContact(string $vendorId): array;

    /**
     * Get vendor's price history for a product
     * 
     * @param string $vendorId Vendor identifier
     * @param string $productId Product identifier
     * @return array<array{price: float, date: string, po_id: string}> Historical prices
     */
    public function getVendorPriceHistory(string $vendorId, string $productId): array;

    /**
     * Get RFQ win rate for vendor
     * 
     * @param string $vendorId Vendor identifier
     * @return float Win rate (0.0 to 1.0, e.g., 0.85 = 85% win rate)
     */
    public function getRfqWinRate(string $vendorId): float;

    /**
     * Get average budget proximity score
     * Score near 1.0 indicates vendor always prices close to budget limit
     * 
     * @param string $vendorId Vendor identifier
     * @return float Average ratio of vendor quote to budget (0.0 to 1.0+)
     */
    public function getAverageBudgetProximity(string $vendorId): float;

    /**
     * Get frequency of specific requester using this vendor
     * 
     * @param string $requesterId Requester identifier
     * @param string $vendorId Vendor identifier
     * @return float Frequency (0.0 to 1.0, ratio of requester's orders using this vendor)
     */
    public function getRequesterVendorFrequency(string $requesterId, string $vendorId): float;

    /**
     * Count after-hours submissions for requester-vendor pair
     * 
     * @param string $requesterId Requester identifier
     * @param string $vendorId Vendor identifier
     * @return int Number of requisitions submitted outside business hours
     */
    public function getAfterHoursSubmissionCount(string $requesterId, string $vendorId): int;

    /**
     * Count suspicious split orders (orders split to avoid approval thresholds)
     * 
     * @param string $vendorId Vendor identifier
     * @return int Number of suspected split orders
     */
    public function getSuspiciousSplitOrderCount(string $vendorId): int;

    /**
     * Count missing certifications for vendor
     * 
     * @param string $vendorId Vendor identifier
     * @return int Number of required certifications not on file
     */
    public function getMissingCertificationCount(string $vendorId): int;

    /**
     * Detect gaps in vendor invoice numbering sequence
     * 
     * @param string $vendorId Vendor identifier
     * @return int Number of gaps detected in invoice sequences
     */
    public function getInvoiceNumberGapCount(string $vendorId): int;

    /**
     * Count payment term changes for vendor
     * 
     * @param string $vendorId Vendor identifier
     * @return int Number of times payment terms were modified
     */
    public function getPaymentTermChangeCount(string $vendorId): int;

    /**
     * Get rush order frequency for vendor
     * 
     * @param string $vendorId Vendor identifier
     * @return float Ratio of rush orders to total orders (0.0 to 1.0)
     */
    public function getRushOrderFrequency(string $vendorId): float;

    /**
     * Count approval bypass attempts
     * 
     * @param string $vendorId Vendor identifier
     * @return int Number of times standard approval was bypassed
     */
    public function getApprovalBypassAttempts(string $vendorId): int;

    /**
     * Check if requester-vendor pairing is statistically unusual
     * 
     * @param string $requesterId Requester identifier
     * @param string $vendorId Vendor identifier
     * @return bool True if pairing is anomalous
     */
    public function isUnusualRequesterVendorPairing(string $requesterId, string $vendorId): bool;

    /**
     * Get document metadata anomaly score
     * Detects file creation dates inconsistent with invoice dates, etc.
     * 
     * @param string $vendorId Vendor identifier
     * @return float Anomaly score (0.0 = no anomalies, 1.0 = severe anomalies)
     */
    public function getDocumentMetadataAnomalyScore(string $vendorId): float;

    /**
     * Check if vendor registration is verified
     * 
     * @param string $vendorId Vendor identifier
     * @return bool True if registration verified with government registry
     */
    public function isRegistrationVerified(string $vendorId): bool;

    /**
     * Get vendor lifetime value (total spend)
     * 
     * @param string $vendorId Vendor identifier
     * @return float Total amount spent with this vendor
     */
    public function getVendorLifetimeValue(string $vendorId): float;
}
