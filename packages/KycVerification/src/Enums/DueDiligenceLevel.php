<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Enums;

/**
 * Due Diligence level for KYC verification.
 * 
 * Determines the extent of verification required based on risk.
 */
enum DueDiligenceLevel: string
{
    /**
     * Simplified Due Diligence (SDD)
     * For low-risk parties with limited transaction volumes
     */
    case SIMPLIFIED = 'simplified';

    /**
     * Standard/Customer Due Diligence (CDD)
     * Default level for most parties
     */
    case STANDARD = 'standard';

    /**
     * Enhanced Due Diligence (EDD)
     * For high-risk parties, PEPs, high-risk jurisdictions
     */
    case ENHANCED = 'enhanced';

    /**
     * Get required document types for this due diligence level
     * 
     * @return array<DocumentType>
     */
    public function requiredDocumentTypes(): array
    {
        return match ($this) {
            self::SIMPLIFIED => [
                DocumentType::NATIONAL_ID,
            ],
            self::STANDARD => [
                DocumentType::PASSPORT,
                DocumentType::UTILITY_BILL,
            ],
            self::ENHANCED => [
                DocumentType::PASSPORT,
                DocumentType::UTILITY_BILL,
                DocumentType::BANK_STATEMENT,
                DocumentType::SELFIE,
            ],
        };
    }

    /**
     * Get required corporate document types for this due diligence level
     * 
     * @return array<DocumentType>
     */
    public function requiredCorporateDocumentTypes(): array
    {
        return match ($this) {
            self::SIMPLIFIED => [
                DocumentType::BUSINESS_LICENSE,
            ],
            self::STANDARD => [
                DocumentType::CERTIFICATE_OF_INCORPORATION,
                DocumentType::BUSINESS_LICENSE,
                DocumentType::PROOF_OF_ADDRESS_BUSINESS,
            ],
            self::ENHANCED => [
                DocumentType::CERTIFICATE_OF_INCORPORATION,
                DocumentType::ARTICLES_OF_ASSOCIATION,
                DocumentType::BUSINESS_LICENSE,
                DocumentType::TAX_REGISTRATION,
                DocumentType::SHAREHOLDER_REGISTER,
                DocumentType::UBO_DECLARATION,
                DocumentType::FINANCIAL_STATEMENTS,
            ],
        };
    }

    /**
     * Check if UBO tracking is required
     */
    public function requiresUboTracking(): bool
    {
        return in_array($this, [
            self::STANDARD,
            self::ENHANCED,
        ], true);
    }

    /**
     * Get minimum verification score required
     */
    public function minimumVerificationScore(): int
    {
        return match ($this) {
            self::SIMPLIFIED => 60,
            self::STANDARD => 75,
            self::ENHANCED => 90,
        };
    }

    /**
     * Check if ongoing monitoring is required
     */
    public function requiresOngoingMonitoring(): bool
    {
        return $this !== self::SIMPLIFIED;
    }

    /**
     * Check if source of funds verification is required
     */
    public function requiresSourceOfFunds(): bool
    {
        return $this === self::ENHANCED;
    }

    /**
     * Check if source of wealth verification is required
     */
    public function requiresSourceOfWealth(): bool
    {
        return $this === self::ENHANCED;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SIMPLIFIED => 'Simplified Due Diligence (SDD)',
            self::STANDARD => 'Standard Due Diligence (CDD)',
            self::ENHANCED => 'Enhanced Due Diligence (EDD)',
        };
    }

    /**
     * Get short label
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::SIMPLIFIED => 'SDD',
            self::STANDARD => 'CDD',
            self::ENHANCED => 'EDD',
        };
    }
}
