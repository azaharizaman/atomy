<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Enums;

/**
 * Document types accepted for KYC verification.
 * 
 * Follows international standards for identity verification documents.
 */
enum DocumentType: string
{
    // Primary Identity Documents
    case PASSPORT = 'passport';
    case NATIONAL_ID = 'national_id';
    case DRIVERS_LICENSE = 'drivers_license';
    case RESIDENCE_PERMIT = 'residence_permit';
    case REFUGEE_DOCUMENT = 'refugee_document';

    // Secondary Identity Documents
    case VOTER_ID = 'voter_id';
    case MILITARY_ID = 'military_id';
    case GOVERNMENT_ID = 'government_id';

    // Address Verification Documents
    case UTILITY_BILL = 'utility_bill';
    case BANK_STATEMENT = 'bank_statement';
    case TAX_DOCUMENT = 'tax_document';
    case RENTAL_AGREEMENT = 'rental_agreement';
    case MORTGAGE_STATEMENT = 'mortgage_statement';

    // Corporate Documents
    case CERTIFICATE_OF_INCORPORATION = 'certificate_of_incorporation';
    case ARTICLES_OF_ASSOCIATION = 'articles_of_association';
    case BUSINESS_LICENSE = 'business_license';
    case TAX_REGISTRATION = 'tax_registration';
    case SHAREHOLDER_REGISTER = 'shareholder_register';
    case BOARD_RESOLUTION = 'board_resolution';
    case FINANCIAL_STATEMENTS = 'financial_statements';
    case PROOF_OF_ADDRESS_BUSINESS = 'proof_of_address_business';

    // Beneficial Ownership Documents
    case UBO_DECLARATION = 'ubo_declaration';
    case OWNERSHIP_STRUCTURE_CHART = 'ownership_structure_chart';
    case TRUST_DEED = 'trust_deed';

    // Additional Documents
    case SELFIE = 'selfie';
    case LIVENESS_CHECK = 'liveness_check';
    case VIDEO_VERIFICATION = 'video_verification';
    case SIGNATURE_SPECIMEN = 'signature_specimen';

    /**
     * Check if this is a primary identity document
     */
    public function isPrimaryId(): bool
    {
        return in_array($this, [
            self::PASSPORT,
            self::NATIONAL_ID,
            self::DRIVERS_LICENSE,
            self::RESIDENCE_PERMIT,
            self::REFUGEE_DOCUMENT,
        ], true);
    }

    /**
     * Check if this is an address verification document
     */
    public function isAddressDocument(): bool
    {
        return in_array($this, [
            self::UTILITY_BILL,
            self::BANK_STATEMENT,
            self::TAX_DOCUMENT,
            self::RENTAL_AGREEMENT,
            self::MORTGAGE_STATEMENT,
            self::PROOF_OF_ADDRESS_BUSINESS,
        ], true);
    }

    /**
     * Check if this is a corporate document
     */
    public function isCorporateDocument(): bool
    {
        return in_array($this, [
            self::CERTIFICATE_OF_INCORPORATION,
            self::ARTICLES_OF_ASSOCIATION,
            self::BUSINESS_LICENSE,
            self::TAX_REGISTRATION,
            self::SHAREHOLDER_REGISTER,
            self::BOARD_RESOLUTION,
            self::FINANCIAL_STATEMENTS,
            self::PROOF_OF_ADDRESS_BUSINESS,
        ], true);
    }

    /**
     * Check if this is a beneficial ownership document
     */
    public function isUboDocument(): bool
    {
        return in_array($this, [
            self::UBO_DECLARATION,
            self::OWNERSHIP_STRUCTURE_CHART,
            self::TRUST_DEED,
            self::SHAREHOLDER_REGISTER,
        ], true);
    }

    /**
     * Check if this document has expiry tracking
     */
    public function hasExpiry(): bool
    {
        return in_array($this, [
            self::PASSPORT,
            self::NATIONAL_ID,
            self::DRIVERS_LICENSE,
            self::RESIDENCE_PERMIT,
            self::REFUGEE_DOCUMENT,
            self::MILITARY_ID,
            self::BUSINESS_LICENSE,
        ], true);
    }

    /**
     * Get maximum allowed age in days for address documents
     */
    public function maxAgeDays(): ?int
    {
        return match ($this) {
            self::UTILITY_BILL, self::BANK_STATEMENT => 90,
            self::TAX_DOCUMENT => 365,
            self::RENTAL_AGREEMENT, self::MORTGAGE_STATEMENT => 365,
            self::FINANCIAL_STATEMENTS => 365,
            default => null,
        };
    }

    /**
     * Get verification weight (used in scoring)
     * Higher weight = more reliable document
     */
    public function verificationWeight(): int
    {
        return match ($this) {
            self::PASSPORT => 100,
            self::NATIONAL_ID => 95,
            self::DRIVERS_LICENSE => 85,
            self::RESIDENCE_PERMIT => 90,
            self::REFUGEE_DOCUMENT => 80,
            self::VOTER_ID => 70,
            self::MILITARY_ID => 85,
            self::GOVERNMENT_ID => 75,
            self::UTILITY_BILL => 60,
            self::BANK_STATEMENT => 65,
            self::TAX_DOCUMENT => 70,
            self::RENTAL_AGREEMENT => 55,
            self::MORTGAGE_STATEMENT => 60,
            self::CERTIFICATE_OF_INCORPORATION => 95,
            self::ARTICLES_OF_ASSOCIATION => 85,
            self::BUSINESS_LICENSE => 80,
            self::TAX_REGISTRATION => 85,
            self::SHAREHOLDER_REGISTER => 80,
            self::BOARD_RESOLUTION => 75,
            self::FINANCIAL_STATEMENTS => 70,
            self::PROOF_OF_ADDRESS_BUSINESS => 60,
            self::UBO_DECLARATION => 70,
            self::OWNERSHIP_STRUCTURE_CHART => 65,
            self::TRUST_DEED => 80,
            self::SELFIE => 50,
            self::LIVENESS_CHECK => 60,
            self::VIDEO_VERIFICATION => 75,
            self::SIGNATURE_SPECIMEN => 40,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PASSPORT => 'Passport',
            self::NATIONAL_ID => 'National ID Card',
            self::DRIVERS_LICENSE => 'Driver\'s License',
            self::RESIDENCE_PERMIT => 'Residence Permit',
            self::REFUGEE_DOCUMENT => 'Refugee Document',
            self::VOTER_ID => 'Voter ID',
            self::MILITARY_ID => 'Military ID',
            self::GOVERNMENT_ID => 'Government ID',
            self::UTILITY_BILL => 'Utility Bill',
            self::BANK_STATEMENT => 'Bank Statement',
            self::TAX_DOCUMENT => 'Tax Document',
            self::RENTAL_AGREEMENT => 'Rental Agreement',
            self::MORTGAGE_STATEMENT => 'Mortgage Statement',
            self::CERTIFICATE_OF_INCORPORATION => 'Certificate of Incorporation',
            self::ARTICLES_OF_ASSOCIATION => 'Articles of Association',
            self::BUSINESS_LICENSE => 'Business License',
            self::TAX_REGISTRATION => 'Tax Registration',
            self::SHAREHOLDER_REGISTER => 'Shareholder Register',
            self::BOARD_RESOLUTION => 'Board Resolution',
            self::FINANCIAL_STATEMENTS => 'Financial Statements',
            self::PROOF_OF_ADDRESS_BUSINESS => 'Proof of Business Address',
            self::UBO_DECLARATION => 'UBO Declaration',
            self::OWNERSHIP_STRUCTURE_CHART => 'Ownership Structure Chart',
            self::TRUST_DEED => 'Trust Deed',
            self::SELFIE => 'Selfie',
            self::LIVENESS_CHECK => 'Liveness Check',
            self::VIDEO_VERIFICATION => 'Video Verification',
            self::SIGNATURE_SPECIMEN => 'Signature Specimen',
        };
    }
}
