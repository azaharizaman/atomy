<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Payment methods supported for vendor payments.
 */
enum PaymentMethod: string
{
    case ACH = 'ach';
    case WIRE = 'wire';
    case CHECK = 'check';
    case VIRTUAL_CARD = 'virtual_card';
    case CREDIT_CARD = 'credit_card';

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACH => 'ACH Transfer',
            self::WIRE => 'Wire Transfer',
            self::CHECK => 'Check',
            self::VIRTUAL_CARD => 'Virtual Card',
            self::CREDIT_CARD => 'Credit Card',
        };
    }

    /**
     * Get typical processing time in business days.
     */
    public function getProcessingDays(): int
    {
        return match ($this) {
            self::ACH => 2,
            self::WIRE => 1,
            self::CHECK => 5,
            self::VIRTUAL_CARD => 0,
            self::CREDIT_CARD => 0,
        };
    }

    /**
     * Check if method supports same-day processing.
     */
    public function supportsSameDay(): bool
    {
        return match ($this) {
            self::ACH => false,
            self::WIRE => true,
            self::CHECK => false,
            self::VIRTUAL_CARD => true,
            self::CREDIT_CARD => true,
        };
    }

    /**
     * Check if method requires bank account details.
     */
    public function requiresBankAccount(): bool
    {
        return match ($this) {
            self::ACH => true,
            self::WIRE => true,
            self::CHECK => false,
            self::VIRTUAL_CARD => false,
            self::CREDIT_CARD => false,
        };
    }

    /**
     * Check if method requires mailing address.
     */
    public function requiresMailingAddress(): bool
    {
        return match ($this) {
            self::ACH => false,
            self::WIRE => false,
            self::CHECK => true,
            self::VIRTUAL_CARD => false,
            self::CREDIT_CARD => false,
        };
    }

    /**
     * Get typical fee structure description.
     */
    public function getFeeDescription(): string
    {
        return match ($this) {
            self::ACH => 'Low fee ($0.20-$1.50 per transaction)',
            self::WIRE => 'Higher fee ($15-$50 per transaction)',
            self::CHECK => 'Low fee (postage + processing)',
            self::VIRTUAL_CARD => 'May earn rebates (1-2%)',
            self::CREDIT_CARD => 'Processing fee (2-3%)',
        };
    }

    /**
     * Check if method is suitable for international payments.
     */
    public function supportsInternational(): bool
    {
        return match ($this) {
            self::ACH => false,
            self::WIRE => true,
            self::CHECK => true,
            self::VIRTUAL_CARD => true,
            self::CREDIT_CARD => true,
        };
    }

    /**
     * Get the priority order for auto-selection (lower is preferred).
     */
    public function getSelectionPriority(): int
    {
        return match ($this) {
            self::ACH => 1,            // Most preferred - lowest cost
            self::VIRTUAL_CARD => 2,   // May earn rebates
            self::WIRE => 3,           // For urgent/international
            self::CHECK => 4,          // Fallback
            self::CREDIT_CARD => 5,    // Highest cost
        };
    }
}
