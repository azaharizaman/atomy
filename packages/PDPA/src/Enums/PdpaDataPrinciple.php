<?php

declare(strict_types=1);

namespace Nexus\PDPA\Enums;

/**
 * PDPA 2010 Data Protection Principles.
 *
 * Malaysia PDPA has 7 data protection principles in Part II.
 *
 * @see https://www.pdp.gov.my/jpdpv2/laws/personal-data-protection-act-2010/
 */
enum PdpaDataPrinciple: string
{
    /**
     * Section 6: General Principle.
     * Personal data shall not be processed unless consent given.
     */
    case GENERAL = 'general';

    /**
     * Section 7: Notice and Choice Principle.
     * Data subject must be informed of purpose and their rights.
     */
    case NOTICE_AND_CHOICE = 'notice_and_choice';

    /**
     * Section 8: Disclosure Principle.
     * Personal data shall not be disclosed without consent.
     */
    case DISCLOSURE = 'disclosure';

    /**
     * Section 9: Security Principle.
     * Practical steps must be taken to protect personal data.
     */
    case SECURITY = 'security';

    /**
     * Section 10: Retention Principle.
     * Personal data shall not be kept longer than necessary.
     */
    case RETENTION = 'retention';

    /**
     * Section 11: Data Integrity Principle.
     * Personal data must be accurate, complete, and up-to-date.
     */
    case DATA_INTEGRITY = 'data_integrity';

    /**
     * Section 12: Access Principle.
     * Data subject has right to access and correct their data.
     */
    case ACCESS = 'access';

    /**
     * Get the section reference.
     */
    public function getSection(): int
    {
        return match ($this) {
            self::GENERAL => 6,
            self::NOTICE_AND_CHOICE => 7,
            self::DISCLOSURE => 8,
            self::SECURITY => 9,
            self::RETENTION => 10,
            self::DATA_INTEGRITY => 11,
            self::ACCESS => 12,
        };
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::GENERAL => 'General Principle (Section 6)',
            self::NOTICE_AND_CHOICE => 'Notice and Choice Principle (Section 7)',
            self::DISCLOSURE => 'Disclosure Principle (Section 8)',
            self::SECURITY => 'Security Principle (Section 9)',
            self::RETENTION => 'Retention Principle (Section 10)',
            self::DATA_INTEGRITY => 'Data Integrity Principle (Section 11)',
            self::ACCESS => 'Access Principle (Section 12)',
        };
    }

    /**
     * Get description of the principle.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::GENERAL => 'Personal data shall not be processed unless the data subject has given consent, or processing is necessary for specific lawful purposes.',
            self::NOTICE_AND_CHOICE => 'Data users must provide notice to data subjects and inform them in writing of the purpose for data collection, rights to access and correct, classes of third parties to whom data may be disclosed, and whether it is obligatory to supply data.',
            self::DISCLOSURE => 'Personal data shall not be disclosed without consent, except where required by law or for the purpose disclosed in the notice.',
            self::SECURITY => 'Security measures and practical steps must be taken to protect personal data from loss, misuse, unauthorized access, disclosure, alteration, or destruction.',
            self::RETENTION => 'Personal data shall not be kept longer than is necessary for the fulfillment of the purpose and shall be destroyed or anonymized when no longer required.',
            self::DATA_INTEGRITY => 'A data user shall take reasonable steps to ensure personal data is accurate, complete, not misleading, and kept up-to-date.',
            self::ACCESS => 'A data subject has the right to access personal data and to correct it if inaccurate, incomplete, misleading, or not up-to-date.',
        };
    }

    /**
     * Get related exceptions (if any).
     *
     * @return array<string>
     */
    public function getExceptions(): array
    {
        return match ($this) {
            self::GENERAL => [
                'Performance of contract',
                'Compliance with legal obligation',
                'Protection of vital interests',
                'Administration of justice',
                'Legal proceedings',
            ],
            self::DISCLOSURE => [
                'Required by any law',
                'For purpose disclosed in notice',
                'Consent of data subject',
                'Prevention or detection of crime',
                'Assessment or collection of tax',
            ],
            self::ACCESS => [
                'Where disclosure would prejudice prevention/detection of crime',
                'Where disclosure would compromise negotiations',
                'Where data subject has already been informed',
            ],
            default => [],
        };
    }

    /**
     * Get maximum penalty for violation (RM).
     *
     * Under PDPA 2010, violations can result in fines up to RM500,000
     * and/or imprisonment up to 3 years.
     */
    public function getMaximumPenalty(): int
    {
        return 500_000; // RM 500,000
    }
}
