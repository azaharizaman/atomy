<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Document retention categories with regulatory compliance periods.
 *
 * Based on SOX, IRS, and industry-standard retention requirements.
 */
enum RetentionCategory: string
{
    case SOX_FINANCIAL_DATA = 'sox_financial_data';
    case VENDOR_CONTRACTS = 'vendor_contracts';
    case RFQ_DATA = 'rfq_data';
    case PURCHASE_ORDERS = 'purchase_orders';
    case INVOICES_PAYABLE = 'invoices_payable';
    case PAYMENT_RECORDS = 'payment_records';
    case TAX_DOCUMENTS = 'tax_documents';
    case AUDIT_WORKPAPERS = 'audit_workpapers';
    case CORRESPONDENCE = 'correspondence';
    case GENERAL_AP = 'general_ap';

    /**
     * Get retention period in years.
     */
    public function getRetentionYears(): int
    {
        return match ($this) {
            self::SOX_FINANCIAL_DATA => 7,
            self::VENDOR_CONTRACTS => 3,
            self::RFQ_DATA => 2,
            self::PURCHASE_ORDERS => 7,
            self::INVOICES_PAYABLE => 7,
            self::PAYMENT_RECORDS => 7,
            self::TAX_DOCUMENTS => 7,
            self::AUDIT_WORKPAPERS => 7,
            self::CORRESPONDENCE => 3,
            self::GENERAL_AP => 5,
        };
    }

    /**
     * Get regulatory basis for retention requirement.
     */
    public function getRegulatoryBasis(): string
    {
        return match ($this) {
            self::SOX_FINANCIAL_DATA => 'Sarbanes-Oxley Act Section 802',
            self::VENDOR_CONTRACTS => 'Statute of Limitations + UCC',
            self::RFQ_DATA => 'Industry Best Practice',
            self::PURCHASE_ORDERS => 'IRS Publication 583 / SOX',
            self::INVOICES_PAYABLE => 'IRS Publication 583 / SOX',
            self::PAYMENT_RECORDS => 'IRS Publication 583 / SOX',
            self::TAX_DOCUMENTS => 'IRS Publication 583',
            self::AUDIT_WORKPAPERS => 'SOX Section 103',
            self::CORRESPONDENCE => 'Industry Best Practice',
            self::GENERAL_AP => 'IRS Publication 583',
        };
    }

    /**
     * Get recommended disposal method.
     */
    public function getDisposalMethod(): string
    {
        return match ($this) {
            self::SOX_FINANCIAL_DATA,
            self::TAX_DOCUMENTS,
            self::AUDIT_WORKPAPERS => 'SECURE_DELETE_WITH_CERTIFICATION',
            self::VENDOR_CONTRACTS,
            self::PAYMENT_RECORDS,
            self::INVOICES_PAYABLE,
            self::PURCHASE_ORDERS => 'SECURE_DELETE',
            self::RFQ_DATA,
            self::CORRESPONDENCE => 'STANDARD_DELETE',
            self::GENERAL_AP => 'ARCHIVE_THEN_DELETE',
        };
    }

    /**
     * Check if category requires legal hold consideration.
     */
    public function requiresLegalHoldCheck(): bool
    {
        return match ($this) {
            self::SOX_FINANCIAL_DATA,
            self::VENDOR_CONTRACTS,
            self::INVOICES_PAYABLE,
            self::PAYMENT_RECORDS,
            self::TAX_DOCUMENTS,
            self::AUDIT_WORKPAPERS => true,
            default => false,
        };
    }

    /**
     * Check if category is subject to SOX compliance.
     */
    public function isSubjectToSox(): bool
    {
        return match ($this) {
            self::SOX_FINANCIAL_DATA,
            self::PURCHASE_ORDERS,
            self::INVOICES_PAYABLE,
            self::PAYMENT_RECORDS,
            self::AUDIT_WORKPAPERS => true,
            default => false,
        };
    }

    /**
     * Get all categories by regulatory requirement.
     *
     * @return array<string, array<self>>
     */
    public static function byRegulation(): array
    {
        $categories = [];
        foreach (self::cases() as $case) {
            $regulation = $case->getRegulatoryBasis();
            if (!isset($categories[$regulation])) {
                $categories[$regulation] = [];
            }
            $categories[$regulation][] = $case;
        }
        return $categories;
    }
}
