<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Enums;

/**
 * Categories for data retention policies.
 *
 * Defines standard retention categories for organizing retention rules.
 */
enum RetentionCategory: string
{
    /**
     * Customer/client data.
     */
    case CUSTOMER = 'customer';

    /**
     * Employee/HR data.
     */
    case EMPLOYEE = 'employee';

    /**
     * Financial/accounting records.
     */
    case FINANCIAL = 'financial';

    /**
     * Legal/contractual documents.
     */
    case LEGAL = 'legal';

    /**
     * Marketing and communications.
     */
    case MARKETING = 'marketing';

    /**
     * System/technical logs.
     */
    case TECHNICAL = 'technical';

    /**
     * Transaction records.
     */
    case TRANSACTION = 'transaction';

    /**
     * Medical/health records.
     */
    case MEDICAL = 'medical';

    /**
     * Audit trail data.
     */
    case AUDIT = 'audit';

    /**
     * Temporary/session data.
     */
    case TEMPORARY = 'temporary';

    /**
     * Get default retention period in months.
     * These are general guidelines based on common legal requirements.
     */
    public function getDefaultRetentionMonths(): int
    {
        return match ($this) {
            self::FINANCIAL => 84, // 7 years (tax requirements)
            self::LEGAL => 120, // 10 years (statute of limitations)
            self::MEDICAL => 120, // 10 years (medical record requirements)
            self::EMPLOYEE => 84, // 7 years after employment ends
            self::AUDIT => 84, // 7 years (compliance requirements)
            self::CUSTOMER => 36, // 3 years after relationship ends
            self::TRANSACTION => 60, // 5 years
            self::MARKETING => 24, // 2 years
            self::TECHNICAL => 12, // 1 year
            self::TEMPORARY => 1, // 1 month or less
        };
    }

    /**
     * Check if legal hold can override automatic deletion.
     */
    public function allowsLegalHold(): bool
    {
        return match ($this) {
            self::TEMPORARY => false, // Session data should always be purged
            default => true,
        };
    }

    /**
     * Check if this category typically requires secure deletion.
     */
    public function requiresSecureDeletion(): bool
    {
        return match ($this) {
            self::FINANCIAL,
            self::LEGAL,
            self::MEDICAL,
            self::EMPLOYEE,
            self::CUSTOMER => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer Data',
            self::EMPLOYEE => 'Employee Data',
            self::FINANCIAL => 'Financial Records',
            self::LEGAL => 'Legal Documents',
            self::MARKETING => 'Marketing Data',
            self::TECHNICAL => 'Technical Logs',
            self::TRANSACTION => 'Transaction Records',
            self::MEDICAL => 'Medical Records',
            self::AUDIT => 'Audit Trail',
            self::TEMPORARY => 'Temporary Data',
        };
    }
}
