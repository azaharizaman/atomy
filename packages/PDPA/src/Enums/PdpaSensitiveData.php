<?php

declare(strict_types=1);

namespace Nexus\PDPA\Enums;

/**
 * PDPA 2010 Sensitive Personal Data categories.
 *
 * Section 40 defines sensitive personal data that requires explicit consent.
 *
 * @see https://www.pdp.gov.my/jpdpv2/laws/personal-data-protection-act-2010/
 */
enum PdpaSensitiveData: string
{
    /**
     * Physical or mental health or condition.
     */
    case HEALTH = 'health';

    /**
     * Political opinions.
     */
    case POLITICAL_OPINION = 'political_opinion';

    /**
     * Religious beliefs or other beliefs of a similar nature.
     */
    case RELIGIOUS_BELIEF = 'religious_belief';

    /**
     * Commission or alleged commission of any offence.
     */
    case CRIMINAL_OFFENCE = 'criminal_offence';

    /**
     * Any other personal data as determined by the Minister.
     */
    case OTHER_PERSONAL_DATA = 'other_personal_data';

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::HEALTH => 'Physical or Mental Health',
            self::POLITICAL_OPINION => 'Political Opinions',
            self::RELIGIOUS_BELIEF => 'Religious Beliefs or Other Similar Beliefs',
            self::CRIMINAL_OFFENCE => 'Commission or Alleged Commission of Offence',
            self::OTHER_PERSONAL_DATA => 'Other Personal Data as Prescribed by Minister',
        };
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::HEALTH => 'Personal data relating to the physical or mental health or condition of a data subject.',
            self::POLITICAL_OPINION => 'Personal data relating to the political opinions of a data subject.',
            self::RELIGIOUS_BELIEF => 'Personal data relating to the religious beliefs or other beliefs of a similar nature of a data subject.',
            self::CRIMINAL_OFFENCE => 'Personal data relating to the commission or alleged commission of any offence by a data subject, or any proceedings for any offence committed or alleged to have been committed.',
            self::OTHER_PERSONAL_DATA => 'Any other personal data as the Minister may determine to be sensitive personal data by order published in the Gazette.',
        };
    }

    /**
     * Get section reference number.
     */
    public function getSection(): int
    {
        return 40;
    }

    /**
     * Check if category requires explicit consent.
     *
     * All sensitive data categories require explicit consent under Section 40.
     */
    public function requiresExplicitConsent(): bool
    {
        return true;
    }

    /**
     * Get the legal basis requirement.
     */
    public function getLegalBasisRequirement(): string
    {
        return 'Explicit consent from data subject required under PDPA Section 40.';
    }

    /**
     * Get examples of data that falls under this category.
     *
     * @return array<string>
     */
    public function getExamples(): array
    {
        return match ($this) {
            self::HEALTH => [
                'Medical records and history',
                'Mental health assessments',
                'Physical disability information',
                'Hospital admission records',
                'Prescription medications',
            ],
            self::POLITICAL_OPINION => [
                'Political party membership',
                'Voting preferences',
                'Political donations',
                'Participation in political activities',
            ],
            self::RELIGIOUS_BELIEF => [
                'Religious affiliation',
                'Place of worship attendance',
                'Religious dietary requirements',
                'Philosophical beliefs',
            ],
            self::CRIMINAL_OFFENCE => [
                'Criminal conviction records',
                'Pending criminal charges',
                'Police investigation records',
                'Court proceedings records',
            ],
            self::OTHER_PERSONAL_DATA => [
                'Data prescribed by Minister through Gazette',
                'Categories determined by regulatory order',
            ],
        };
    }

    /**
     * Get exceptions where processing is allowed without explicit consent.
     *
     * @return array<string>
     */
    public function getExceptions(): array
    {
        return match ($this) {
            self::HEALTH => [
                'Medical purposes (medical treatment, management of healthcare services)',
                'Necessary to protect vital interests of data subject or another person',
            ],
            self::CRIMINAL_OFFENCE => [
                'Administration of justice',
                'Compliance with legal obligation',
                'Prevention or detection of crime',
            ],
            default => [
                'Required by any law',
                'Legal proceedings',
                'Protection of vital interests',
            ],
        };
    }

    /**
     * Get storage recommendations.
     */
    public function getStorageRecommendation(): string
    {
        return match ($this) {
            self::HEALTH => 'Must be stored with encryption and strict access controls. Retention as per medical record requirements.',
            self::CRIMINAL_OFFENCE => 'Access should be strictly limited. Consider regular audits of access logs.',
            default => 'Store with enhanced security measures. Implement principle of least privilege for access.',
        };
    }

    /**
     * Get section reference.
     */
    public function getSectionReference(): string
    {
        return 'PDPA 2010, Section 40';
    }
}
