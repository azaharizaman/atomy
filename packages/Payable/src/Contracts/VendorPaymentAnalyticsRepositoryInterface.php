<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

use DateTimeImmutable;

/**
 * Vendor payment analytics repository interface
 * 
 * Provides aggregated historical data for duplicate payment detection
 * and vendor payment pattern analysis.
 */
interface VendorPaymentAnalyticsRepositoryInterface
{
    /**
     * Get recent payment/bill amounts for vendor (for similarity detection)
     * 
     * @param string $vendorId Vendor identifier
     * @param int $days Number of days to look back
     * @param int $limit Maximum number of records
     * @return array<array{invoice_number: string, amount: float, bill_date: DateTimeImmutable}>
     */
    public function getRecentBillsByVendor(string $vendorId, int $days = 30, int $limit = 100): array;
    
    /**
     * Get average invoice amount for vendor
     * 
     * @param string $vendorId Vendor identifier
     * @param int $months Number of months for calculation
     * @return float Average amount, 0.0 if no history
     */
    public function getAverageInvoiceAmount(string $vendorId, int $months = 12): float;
    
    /**
     * Get typical invoice frequency for vendor (in days)
     * 
     * @param string $vendorId Vendor identifier
     * @return float Average days between invoices, 0.0 if insufficient data
     */
    public function getInvoiceFrequencyDays(string $vendorId): float;
    
    /**
     * Get count of historical duplicate incidents for vendor
     * 
     * @param string $vendorId Vendor identifier
     * @return int Number of past duplicate detections
     */
    public function getDuplicateHistoryCount(string $vendorId): int;
    
    /**
     * Get invoice sequence gaps for vendor
     * 
     * Detects missing invoice numbers in sequence (potential fraud indicator).
     * 
     * @param string $vendorId Vendor identifier
     * @return int Number of gaps detected in invoice sequences
     */
    public function getInvoiceSequenceGaps(string $vendorId): int;
    
    /**
     * Get count of payments to vendor in last N days
     * 
     * @param string $vendorId Vendor identifier
     * @param int $days Number of days to look back
     * @return int Payment count
     */
    public function getPaymentCountLastNDays(string $vendorId, int $days = 7): int;
    
    /**
     * Get standard deviation of invoice amounts for vendor
     * 
     * @param string $vendorId Vendor identifier
     * @param int $months Number of months for calculation
     * @return float Standard deviation, 0.0 if insufficient data
     */
    public function getInvoiceAmountStdDev(string $vendorId, int $months = 12): float;
    
    /**
     * Check if vendor has history of split invoicing pattern
     * 
     * @param string $vendorId Vendor identifier
     * @return bool True if suspicious pattern detected
     */
    public function hasSplitInvoicingPattern(string $vendorId): bool;
    
    /**
     * Get timestamp of last payment to vendor
     * 
     * @param string $vendorId Vendor identifier
     * @return DateTimeImmutable|null Last payment date or null if none
     */
    public function getLastPaymentDate(string $vendorId): ?DateTimeImmutable;
}
