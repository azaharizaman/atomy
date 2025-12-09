<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Vendor hold reasons for blocking POs and payments.
 *
 * Hold reasons are categorized by severity:
 * - HARD_BLOCK: Completely blocks all transactions
 * - SOFT_BLOCK: Blocks new transactions but allows existing to complete
 */
enum VendorHoldReason: string
{
    // Hard blocks - completely block all transactions
    case FRAUD_SUSPECTED = 'fraud_suspected';
    case SANCTIONS_LIST = 'sanctions_list';
    case LEGAL_ACTION = 'legal_action';
    case DUPLICATE_VENDOR = 'duplicate_vendor';
    case TERMINATED = 'terminated';

    // Soft blocks - block new transactions, allow existing to complete
    case COMPLIANCE_PENDING = 'compliance_pending';
    case CERTIFICATE_EXPIRED = 'certificate_expired';
    case INSURANCE_EXPIRED = 'insurance_expired';
    case TAX_DOCUMENT_MISSING = 'tax_document_missing';
    case BANK_VERIFICATION_PENDING = 'bank_verification_pending';
    case PERFORMANCE_ISSUE = 'performance_issue';
    case CREDIT_LIMIT_EXCEEDED = 'credit_limit_exceeded';
    case PAYMENT_DISPUTE = 'payment_dispute';
    case QUALITY_ISSUE = 'quality_issue';

    /**
     * Check if this is a hard block that completely stops all transactions.
     */
    public function isHardBlock(): bool
    {
        return match ($this) {
            self::FRAUD_SUSPECTED,
            self::SANCTIONS_LIST,
            self::LEGAL_ACTION,
            self::DUPLICATE_VENDOR,
            self::TERMINATED => true,
            default => false,
        };
    }

    /**
     * Check if this is a soft block that only prevents new transactions.
     */
    public function isSoftBlock(): bool
    {
        return !$this->isHardBlock();
    }

    /**
     * Check if this block can be auto-released when condition is resolved.
     */
    public function isAutoReleasable(): bool
    {
        return match ($this) {
            self::CERTIFICATE_EXPIRED,
            self::INSURANCE_EXPIRED,
            self::TAX_DOCUMENT_MISSING,
            self::BANK_VERIFICATION_PENDING,
            self::CREDIT_LIMIT_EXCEEDED => true,
            default => false,
        };
    }

    /**
     * Get the setting key for this hold reason's workflow configuration.
     */
    public function settingKey(): string
    {
        return 'procurement.vendor_hold.' . $this->value;
    }

    /**
     * Get human-readable description of the hold reason.
     */
    public function description(): string
    {
        return match ($this) {
            self::FRAUD_SUSPECTED => 'Suspected fraudulent activity',
            self::SANCTIONS_LIST => 'Vendor appears on sanctions list',
            self::LEGAL_ACTION => 'Legal action pending or ongoing',
            self::DUPLICATE_VENDOR => 'Duplicate vendor record',
            self::TERMINATED => 'Vendor relationship terminated',
            self::COMPLIANCE_PENDING => 'Compliance documentation pending review',
            self::CERTIFICATE_EXPIRED => 'Business certificate has expired',
            self::INSURANCE_EXPIRED => 'Insurance coverage has expired',
            self::TAX_DOCUMENT_MISSING => 'Required tax documents missing',
            self::BANK_VERIFICATION_PENDING => 'Bank account verification pending',
            self::PERFORMANCE_ISSUE => 'Performance issues identified',
            self::CREDIT_LIMIT_EXCEEDED => 'Credit limit exceeded',
            self::PAYMENT_DISPUTE => 'Payment dispute in progress',
            self::QUALITY_ISSUE => 'Quality issues identified',
        };
    }

    /**
     * Get severity level (1-5) for reporting.
     */
    public function severityLevel(): int
    {
        return match ($this) {
            self::FRAUD_SUSPECTED,
            self::SANCTIONS_LIST => 5,
            self::LEGAL_ACTION,
            self::TERMINATED => 4,
            self::DUPLICATE_VENDOR,
            self::COMPLIANCE_PENDING => 3,
            self::PERFORMANCE_ISSUE,
            self::QUALITY_ISSUE,
            self::PAYMENT_DISPUTE => 2,
            default => 1,
        };
    }
}
