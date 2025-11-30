<?php

declare(strict_types=1);

namespace Nexus\Tax\Enums;

/**
 * Tax Type: Category of tax
 * 
 * Defines the fundamental type/category of tax being calculated.
 * Used for jurisdiction resolution and rate selection.
 */
enum TaxType: string
{
    case VAT = 'vat'; // Value Added Tax (EU, UK, many countries)
    case GST = 'gst'; // Goods and Services Tax (Canada, India, Australia, NZ)
    case SST = 'sst'; // Sales and Service Tax (Malaysia)
    case SalesTax = 'sales_tax'; // State/Local Sales Tax (USA)
    case Excise = 'excise'; // Excise Tax (alcohol, tobacco, fuel)
    case Withholding = 'withholding'; // Withholding Tax (cross-border services)

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::VAT => 'Value Added Tax (VAT)',
            self::GST => 'Goods and Services Tax (GST)',
            self::SST => 'Sales and Service Tax (SST)',
            self::SalesTax => 'Sales Tax',
            self::Excise => 'Excise Tax',
            self::Withholding => 'Withholding Tax',
        };
    }

    /**
     * Check if this is an invoice-based tax (VAT, GST)
     * 
     * Invoice-based taxes allow input tax credits/deductions
     */
    public function isInvoiceBased(): bool
    {
        return match ($this) {
            self::VAT, self::GST => true,
            default => false,
        };
    }

    /**
     * Check if this tax supports reverse charge mechanism
     */
    public function supportsReverseCharge(): bool
    {
        return match ($this) {
            self::VAT, self::GST => true,
            default => false,
        };
    }

    /**
     * Get common jurisdictions that use this tax type
     * 
     * @return array<string>
     */
    public function getCommonJurisdictions(): array
    {
        return match ($this) {
            self::VAT => ['EU-*', 'GB', 'AU', 'NZ', 'ZA'],
            self::GST => ['CA', 'IN', 'AU', 'NZ', 'SG'],
            self::SST => ['MY'],
            self::SalesTax => ['US'],
            self::Excise => ['*'], // Universal
            self::Withholding => ['*'], // Universal
        };
    }
}
