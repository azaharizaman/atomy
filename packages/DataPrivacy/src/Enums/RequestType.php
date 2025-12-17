<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Enums;

/**
 * Types of data subject requests supported across privacy regulations.
 *
 * Common across: GDPR, CCPA, LGPD, PIPEDA, PDPA Malaysia
 */
enum RequestType: string
{
    /**
     * Right of access - request copy of personal data.
     * GDPR Art. 15, PDPA Section 12
     */
    case ACCESS = 'access';

    /**
     * Right to erasure ("right to be forgotten").
     * GDPR Art. 17, PDPA 2024 Amendment
     */
    case ERASURE = 'erasure';

    /**
     * Right to rectification - correct inaccurate data.
     * GDPR Art. 16, PDPA Section 12(2)
     */
    case RECTIFICATION = 'rectification';

    /**
     * Right to restriction of processing.
     * GDPR Art. 18
     */
    case RESTRICTION = 'restriction';

    /**
     * Right to data portability - receive data in machine-readable format.
     * GDPR Art. 20, PDPA 2024 Amendment
     */
    case PORTABILITY = 'portability';

    /**
     * Right to object to processing.
     * GDPR Art. 21, PDPA Section 43
     */
    case OBJECTION = 'objection';

    /**
     * Right not to be subject to automated decision-making.
     * GDPR Art. 22
     */
    case AUTOMATED_DECISION = 'automated_decision';

    /**
     * Get human-readable label for this request type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACCESS => 'Data Access Request',
            self::ERASURE => 'Erasure Request',
            self::RECTIFICATION => 'Rectification Request',
            self::RESTRICTION => 'Restriction of Processing',
            self::PORTABILITY => 'Data Portability Request',
            self::OBJECTION => 'Objection to Processing',
            self::AUTOMATED_DECISION => 'Automated Decision Review',
        };
    }

    /**
     * Check if this request type requires data export.
     */
    public function requiresDataExport(): bool
    {
        return match ($this) {
            self::ACCESS, self::PORTABILITY => true,
            default => false,
        };
    }

    /**
     * Check if this request type can result in data deletion.
     */
    public function canResultInDeletion(): bool
    {
        return $this === self::ERASURE;
    }

    /**
     * Check if this request type modifies data.
     */
    public function modifiesData(): bool
    {
        return match ($this) {
            self::ERASURE, self::RECTIFICATION, self::RESTRICTION => true,
            default => false,
        };
    }

    /**
     * Get default deadline in days for this request type.
     * This is the regulation-agnostic default; extension packages
     * (GDPR, PDPA) may override with regulation-specific deadlines.
     */
    public function getDefaultDeadlineDays(): int
    {
        return match ($this) {
            self::ACCESS => 30,
            self::ERASURE => 30,
            self::RECTIFICATION => 30,
            self::RESTRICTION => 30,
            self::PORTABILITY => 30,
            self::OBJECTION => 30,
            self::AUTOMATED_DECISION => 30,
        };
    }
}
