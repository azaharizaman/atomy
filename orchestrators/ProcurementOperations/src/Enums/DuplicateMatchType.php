<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Types of duplicate invoice matches detected.
 *
 * Each match type has different confidence levels and risk assessments:
 * - EXACT: 100% confidence, highest risk
 * - HIGH_CONFIDENCE: >90% confidence, high risk
 * - MEDIUM_CONFIDENCE: 70-90% confidence, medium risk
 * - LOW_CONFIDENCE: <70% confidence, requires review
 */
enum DuplicateMatchType: string
{
    /**
     * Exact match on vendor + invoice number + amount.
     * Highest confidence - almost certainly a duplicate.
     */
    case EXACT_MATCH = 'exact_match';

    /**
     * Same vendor + invoice number, different amount.
     * Could be amended invoice or typo.
     */
    case INVOICE_NUMBER_MATCH = 'invoice_number_match';

    /**
     * Same vendor + amount + date (within tolerance).
     * Common duplicate pattern.
     */
    case AMOUNT_DATE_MATCH = 'amount_date_match';

    /**
     * Same vendor + amount, different invoice numbers.
     * Could be split invoice or duplicate with different number.
     */
    case AMOUNT_VENDOR_MATCH = 'amount_vendor_match';

    /**
     * Fuzzy match on invoice number (typos, OCR errors).
     * E.g., "INV-001" vs "INV-0O1" (zero vs letter O).
     */
    case FUZZY_INVOICE_NUMBER = 'fuzzy_invoice_number';

    /**
     * Same PO reference across multiple invoices.
     * May indicate over-billing against single PO.
     */
    case PO_REFERENCE_MATCH = 'po_reference_match';

    /**
     * Hash collision on normalized invoice data.
     * Used for quick pre-screening.
     */
    case HASH_COLLISION = 'hash_collision';

    /**
     * Get the confidence level for this match type.
     *
     * @return float Confidence level (0.0 to 1.0)
     */
    public function getConfidenceLevel(): float
    {
        return match ($this) {
            self::EXACT_MATCH => 1.0,
            self::INVOICE_NUMBER_MATCH => 0.95,
            self::HASH_COLLISION => 0.90,
            self::AMOUNT_DATE_MATCH => 0.85,
            self::FUZZY_INVOICE_NUMBER => 0.80,
            self::PO_REFERENCE_MATCH => 0.75,
            self::AMOUNT_VENDOR_MATCH => 0.70,
        };
    }

    /**
     * Get risk level for this match type.
     *
     * @return string Risk level (critical, high, medium, low)
     */
    public function getRiskLevel(): string
    {
        return match ($this) {
            self::EXACT_MATCH => 'critical',
            self::INVOICE_NUMBER_MATCH, self::HASH_COLLISION => 'high',
            self::AMOUNT_DATE_MATCH, self::FUZZY_INVOICE_NUMBER => 'medium',
            self::PO_REFERENCE_MATCH, self::AMOUNT_VENDOR_MATCH => 'low',
        };
    }

    /**
     * Check if this match type should block invoice processing.
     *
     * @return bool True if should block, false if warning only
     */
    public function shouldBlockProcessing(): bool
    {
        return match ($this) {
            self::EXACT_MATCH, self::INVOICE_NUMBER_MATCH => true,
            default => false,
        };
    }

    /**
     * Get human-readable description of this match type.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::EXACT_MATCH => 'Exact duplicate: same vendor, invoice number, and amount',
            self::INVOICE_NUMBER_MATCH => 'Same invoice number from same vendor with different amount',
            self::AMOUNT_DATE_MATCH => 'Same amount and date from same vendor',
            self::AMOUNT_VENDOR_MATCH => 'Same amount from same vendor (different invoice numbers)',
            self::FUZZY_INVOICE_NUMBER => 'Similar invoice number detected (possible typo/OCR error)',
            self::PO_REFERENCE_MATCH => 'Multiple invoices referencing same PO',
            self::HASH_COLLISION => 'Document fingerprint matches existing invoice',
        };
    }

    /**
     * Get recommended action for this match type.
     */
    public function getRecommendedAction(): string
    {
        return match ($this) {
            self::EXACT_MATCH => 'REJECT: This appears to be a duplicate invoice. Review and reject unless intentional resubmission.',
            self::INVOICE_NUMBER_MATCH => 'HOLD: Invoice number already exists. Verify if this is an amendment or error.',
            self::AMOUNT_DATE_MATCH => 'REVIEW: Same amount on same date may indicate duplicate. Verify invoice details.',
            self::AMOUNT_VENDOR_MATCH => 'FLAG: Same amount from vendor may be duplicate. Check for split invoices.',
            self::FUZZY_INVOICE_NUMBER => 'VERIFY: Invoice number similar to existing. Confirm correct invoice number.',
            self::PO_REFERENCE_MATCH => 'AUDIT: Multiple invoices against same PO. Verify PO quantity/amount limits.',
            self::HASH_COLLISION => 'INVESTIGATE: Document content matches existing invoice. May be resubmission.',
        };
    }
}
