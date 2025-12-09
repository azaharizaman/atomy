<?php

declare(strict_types=1);

namespace Nexus\Tax\Enums;

/**
 * Payment Type: Type of payment for WHT determination
 * 
 * Different payment types have different WHT rates.
 * Categorization follows IRS 1099 categories and international standards.
 */
enum PaymentType: string
{
    // Service payments
    case SERVICES = 'services';
    case PROFESSIONAL_SERVICES = 'professional_services';
    case TECHNICAL_SERVICES = 'technical_services';
    case MANAGEMENT_FEES = 'management_fees';
    case CONSULTING = 'consulting';
    
    // Royalties and IP
    case ROYALTIES = 'royalties';
    case LICENSE_FEES = 'license_fees';
    case PATENT_FEES = 'patent_fees';
    case COPYRIGHT_FEES = 'copyright_fees';
    
    // Financial
    case INTEREST = 'interest';
    case DIVIDENDS = 'dividends';
    case CAPITAL_GAINS = 'capital_gains';
    
    // Rental and property
    case RENT = 'rent';
    case REAL_PROPERTY = 'real_property';
    
    // Contract and construction
    case CONTRACT_PAYMENTS = 'contract_payments';
    case CONSTRUCTION = 'construction';
    
    // Other
    case COMMISSIONS = 'commissions';
    case PRIZES_AWARDS = 'prizes_awards';
    case OTHER = 'other';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SERVICES => 'Services',
            self::PROFESSIONAL_SERVICES => 'Professional Services',
            self::TECHNICAL_SERVICES => 'Technical Services',
            self::MANAGEMENT_FEES => 'Management Fees',
            self::CONSULTING => 'Consulting',
            self::ROYALTIES => 'Royalties',
            self::LICENSE_FEES => 'License Fees',
            self::PATENT_FEES => 'Patent Fees',
            self::COPYRIGHT_FEES => 'Copyright Fees',
            self::INTEREST => 'Interest',
            self::DIVIDENDS => 'Dividends',
            self::CAPITAL_GAINS => 'Capital Gains',
            self::RENT => 'Rent',
            self::REAL_PROPERTY => 'Real Property',
            self::CONTRACT_PAYMENTS => 'Contract Payments',
            self::CONSTRUCTION => 'Construction',
            self::COMMISSIONS => 'Commissions',
            self::PRIZES_AWARDS => 'Prizes and Awards',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get US 1099 form category (basic categorization)
     * 
     * Note: Full 1099 classification logic deferred to Phase C
     */
    public function getUS1099Category(): string
    {
        return match ($this) {
            self::SERVICES,
            self::PROFESSIONAL_SERVICES,
            self::CONSULTING => '1099-NEC', // Non-employee compensation
            
            self::RENT,
            self::ROYALTIES,
            self::PRIZES_AWARDS,
            self::OTHER => '1099-MISC',
            
            self::INTEREST => '1099-INT',
            self::DIVIDENDS => '1099-DIV',
            
            default => '1099-MISC',
        };
    }

    /**
     * Check if this payment type typically requires WHT
     */
    public function typicallyRequiresWithholding(): bool
    {
        return match ($this) {
            self::ROYALTIES,
            self::LICENSE_FEES,
            self::PATENT_FEES,
            self::COPYRIGHT_FEES,
            self::INTEREST,
            self::DIVIDENDS,
            self::TECHNICAL_SERVICES,
            self::MANAGEMENT_FEES => true,
            default => false,
        };
    }
}
