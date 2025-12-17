<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Enums;

/**
 * Categories of personal data for privacy classification.
 *
 * Based on GDPR Art. 9 special categories and common privacy classifications.
 */
enum DataCategory: string
{
    /**
     * Basic personal data (name, email, phone, address).
     */
    case PERSONAL = 'personal';

    /**
     * Contact information.
     */
    case CONTACT = 'contact';

    /**
     * Financial data (bank accounts, payment details, income).
     */
    case FINANCIAL = 'financial';

    /**
     * Health and medical data (GDPR Art. 9 special category).
     */
    case HEALTH = 'health';

    /**
     * Biometric data (fingerprints, facial recognition).
     */
    case BIOMETRIC = 'biometric';

    /**
     * Genetic data.
     */
    case GENETIC = 'genetic';

    /**
     * Racial or ethnic origin (GDPR Art. 9 special category).
     */
    case RACIAL_ETHNIC = 'racial_ethnic';

    /**
     * Political opinions (GDPR Art. 9 special category).
     */
    case POLITICAL = 'political';

    /**
     * Religious or philosophical beliefs (GDPR Art. 9 special category).
     */
    case RELIGIOUS = 'religious';

    /**
     * Trade union membership (GDPR Art. 9 special category).
     */
    case TRADE_UNION = 'trade_union';

    /**
     * Sexual orientation/sex life (GDPR Art. 9 special category).
     */
    case SEXUAL = 'sexual';

    /**
     * Criminal convictions and offences.
     */
    case CRIMINAL = 'criminal';

    /**
     * Children's data (requires parental consent).
     */
    case CHILDREN = 'children';

    /**
     * Location data (GPS, IP-based location).
     */
    case LOCATION = 'location';

    /**
     * Online identifiers (IP address, cookies, device IDs).
     */
    case ONLINE_IDENTIFIERS = 'online_identifiers';

    /**
     * Employment data.
     */
    case EMPLOYMENT = 'employment';

    /**
     * Education data.
     */
    case EDUCATION = 'education';

    /**
     * Behavioral/profiling data.
     */
    case BEHAVIORAL = 'behavioral';

    /**
     * Check if this is a special category requiring extra protection.
     * Based on GDPR Art. 9 special categories.
     */
    public function isSpecialCategory(): bool
    {
        return match ($this) {
            self::HEALTH,
            self::BIOMETRIC,
            self::GENETIC,
            self::RACIAL_ETHNIC,
            self::POLITICAL,
            self::RELIGIOUS,
            self::TRADE_UNION,
            self::SEXUAL,
            self::CRIMINAL => true,
            default => false,
        };
    }

    /**
     * Check if this category is sensitive (alias for isSpecialCategory).
     */
    public function isSensitive(): bool
    {
        return $this->isSpecialCategory();
    }

    /**
     * Check if this category requires explicit consent.
     */
    public function requiresExplicitConsent(): bool
    {
        return $this->isSpecialCategory() || $this === self::CHILDREN;
    }

    /**
     * Get the risk level for this data category.
     *
     * @return int Risk level 1-5 (5 = highest risk)
     */
    public function getRiskLevel(): int
    {
        return match ($this) {
            self::GENETIC, self::BIOMETRIC => 5,
            self::HEALTH, self::CRIMINAL, self::SEXUAL, self::CHILDREN => 4,
            self::FINANCIAL, self::RACIAL_ETHNIC, self::POLITICAL, self::RELIGIOUS => 3,
            self::LOCATION, self::BEHAVIORAL, self::EMPLOYMENT => 2,
            default => 1,
        };
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PERSONAL => 'Personal Data',
            self::CONTACT => 'Contact Information',
            self::FINANCIAL => 'Financial Data',
            self::HEALTH => 'Health Data',
            self::BIOMETRIC => 'Biometric Data',
            self::GENETIC => 'Genetic Data',
            self::RACIAL_ETHNIC => 'Racial/Ethnic Origin',
            self::POLITICAL => 'Political Opinions',
            self::RELIGIOUS => 'Religious Beliefs',
            self::TRADE_UNION => 'Trade Union Membership',
            self::SEXUAL => 'Sexual Orientation',
            self::CRIMINAL => 'Criminal Records',
            self::CHILDREN => 'Children\'s Data',
            self::LOCATION => 'Location Data',
            self::ONLINE_IDENTIFIERS => 'Online Identifiers',
            self::EMPLOYMENT => 'Employment Data',
            self::EDUCATION => 'Education Data',
            self::BEHAVIORAL => 'Behavioral Data',
        };
    }

    /**
     * Get recommended retention period in months.
     * These are general guidelines; specific regulations may differ.
     */
    public function getRecommendedRetentionMonths(): int
    {
        return match ($this) {
            self::HEALTH => 120, // 10 years (medical records)
            self::FINANCIAL => 84, // 7 years (tax/accounting)
            self::EMPLOYMENT => 84, // 7 years
            self::CRIMINAL => 0, // Delete immediately when purpose fulfilled
            self::CHILDREN => 24, // 2 years after age of majority
            self::BEHAVIORAL, self::LOCATION => 12, // 1 year
            self::ONLINE_IDENTIFIERS => 6, // 6 months
            default => 36, // 3 years default
        };
    }
}
