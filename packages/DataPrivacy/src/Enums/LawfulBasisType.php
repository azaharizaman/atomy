<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Enums;

/**
 * Generic lawful basis types for data processing.
 *
 * These are universal concepts; specific regulations may have
 * additional or different bases handled by extension packages.
 */
enum LawfulBasisType: string
{
    /**
     * Data subject has given consent to processing.
     */
    case CONSENT = 'consent';

    /**
     * Processing necessary for contract performance.
     */
    case CONTRACT = 'contract';

    /**
     * Processing necessary for legal obligation.
     */
    case LEGAL_OBLIGATION = 'legal_obligation';

    /**
     * Processing necessary to protect vital interests.
     */
    case VITAL_INTERESTS = 'vital_interests';

    /**
     * Processing necessary for public interest/official authority.
     */
    case PUBLIC_INTEREST = 'public_interest';

    /**
     * Processing necessary for legitimate interests (GDPR-specific).
     */
    case LEGITIMATE_INTERESTS = 'legitimate_interests';

    /**
     * Check if this basis requires active consent from data subject.
     */
    public function requiresActiveConsent(): bool
    {
        return $this === self::CONSENT;
    }

    /**
     * Check if this basis requires explicit consent.
     */
    public function requiresExplicitConsent(): bool
    {
        return $this === self::CONSENT;
    }

    /**
     * Check if this basis can be challenged/objected to.
     */
    public function canBeObjectedTo(): bool
    {
        return match ($this) {
            self::PUBLIC_INTEREST, self::LEGITIMATE_INTERESTS => true,
            default => false,
        };
    }

    /**
     * Check if data subject can request erasure under this basis.
     */
    public function allowsErasure(): bool
    {
        return match ($this) {
            self::CONSENT, self::LEGITIMATE_INTERESTS => true,
            self::LEGAL_OBLIGATION, self::PUBLIC_INTEREST => false,
            default => true,
        };
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CONSENT => 'Consent',
            self::CONTRACT => 'Contractual Necessity',
            self::LEGAL_OBLIGATION => 'Legal Obligation',
            self::VITAL_INTERESTS => 'Vital Interests',
            self::PUBLIC_INTEREST => 'Public Interest',
            self::LEGITIMATE_INTERESTS => 'Legitimate Interests',
        };
    }

    /**
     * Get description of this lawful basis.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CONSENT => 'The data subject has given clear consent for processing their personal data for a specific purpose.',
            self::CONTRACT => 'Processing is necessary for the performance of a contract with the data subject.',
            self::LEGAL_OBLIGATION => 'Processing is necessary for compliance with a legal obligation.',
            self::VITAL_INTERESTS => 'Processing is necessary to protect vital interests of the data subject or another person.',
            self::PUBLIC_INTEREST => 'Processing is necessary for performing a task in the public interest or exercise of official authority.',
            self::LEGITIMATE_INTERESTS => 'Processing is necessary for legitimate interests pursued by the controller or a third party.',
        };
    }
}
