<?php

declare(strict_types=1);

namespace Nexus\Tax\Enums;

/**
 * Tax Exemption Reason: Legal reason for tax exemption
 * 
 * Categorizes why a transaction or customer is exempt from tax.
 * Used for compliance reporting and audit trails.
 */
enum TaxExemptionReason: string
{
    case Resale = 'resale'; // Purchased for resale (wholesale)
    case Government = 'government'; // Government entity
    case Nonprofit = 'nonprofit'; // 501(c)(3) or equivalent nonprofit
    case Export = 'export'; // International export
    case Diplomatic = 'diplomatic'; // Diplomatic immunity
    case Agricultural = 'agricultural'; // Agricultural producer

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Resale => 'Resale Certificate',
            self::Government => 'Government Entity',
            self::Nonprofit => 'Nonprofit Organization',
            self::Export => 'International Export',
            self::Diplomatic => 'Diplomatic Immunity',
            self::Agricultural => 'Agricultural Producer',
        };
    }

    /**
     * Check if this exemption reason is typically 100% (full exemption)
     */
    public function isTypicallyFullExemption(): bool
    {
        return match ($this) {
            self::Resale, self::Government, self::Nonprofit, self::Export, self::Diplomatic => true,
            self::Agricultural => false, // Often partial
        };
    }

    /**
     * Check if this exemption requires annual renewal
     */
    public function requiresAnnualRenewal(): bool
    {
        return match ($this) {
            self::Resale, self::Nonprofit, self::Agricultural => true,
            self::Government, self::Export, self::Diplomatic => false,
        };
    }

    /**
     * Get common duration in years for this exemption type
     */
    public function getTypicalDurationYears(): int
    {
        return match ($this) {
            self::Resale => 1, // Annual renewal
            self::Government => 0, // Permanent
            self::Nonprofit => 2, // Biennial
            self::Export => 0, // Per-transaction
            self::Diplomatic => 0, // Permanent
            self::Agricultural => 1, // Annual
        };
    }

    /**
     * Check if PDF certificate upload is required
     */
    public function requiresCertificateUpload(): bool
    {
        return match ($this) {
            self::Resale, self::Nonprofit, self::Agricultural => true,
            self::Government, self::Export, self::Diplomatic => false,
        };
    }
}
