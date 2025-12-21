<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Defines the types of payment rails supported by the package.
 *
 * Payment rails represent different networks and mechanisms for
 * transferring funds between financial institutions.
 */
enum RailType: string
{
    /**
     * Automated Clearing House - US electronic payment network.
     * Supports both credit (push) and debit (pull) transactions.
     */
    case ACH = 'ach';

    /**
     * Wire transfer - Real-time bank-to-bank transfer.
     * Supports both domestic and international (SWIFT) transfers.
     */
    case WIRE = 'wire';

    /**
     * Paper check - Traditional check payment.
     * Includes positive pay and check clearing tracking.
     */
    case CHECK = 'check';

    /**
     * Real-Time Gross Settlement - High-value instant transfers.
     * Typically used for large-value or time-critical payments.
     */
    case RTGS = 'rtgs';

    /**
     * Virtual card - Single or multi-use virtual card numbers.
     * Used for B2B payments with rebate potential.
     */
    case VIRTUAL_CARD = 'virtual_card';

    /**
     * Book transfer - Internal transfer within same institution.
     * No external clearing required.
     */
    case BOOK_TRANSFER = 'book_transfer';

    /**
     * Get a human-readable label for the rail type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACH => 'ACH',
            self::WIRE => 'Wire Transfer',
            self::CHECK => 'Check',
            self::RTGS => 'RTGS',
            self::VIRTUAL_CARD => 'Virtual Card',
            self::BOOK_TRANSFER => 'Book Transfer',
        };
    }

    /**
     * Check if this rail supports credit (push) transactions.
     */
    public function supportsCredit(): bool
    {
        return match ($this) {
            self::ACH,
            self::WIRE,
            self::CHECK,
            self::RTGS,
            self::VIRTUAL_CARD,
            self::BOOK_TRANSFER => true,
        };
    }

    /**
     * Check if this rail supports debit (pull) transactions.
     */
    public function supportsDebit(): bool
    {
        return match ($this) {
            self::ACH => true,
            self::WIRE,
            self::CHECK,
            self::RTGS,
            self::VIRTUAL_CARD,
            self::BOOK_TRANSFER => false,
        };
    }

    /**
     * Check if this rail supports real-time processing.
     */
    public function isRealTime(): bool
    {
        return match ($this) {
            self::WIRE,
            self::RTGS,
            self::BOOK_TRANSFER => true,
            self::ACH,
            self::CHECK,
            self::VIRTUAL_CARD => false,
        };
    }

    /**
     * Get typical settlement time in business days.
     */
    public function typicalSettlementDays(): int
    {
        return match ($this) {
            self::WIRE,
            self::RTGS,
            self::BOOK_TRANSFER => 0,
            self::ACH => 2,
            self::VIRTUAL_CARD => 1,
            self::CHECK => 5,
        };
    }

    /**
     * Check if this rail requires file generation.
     */
    public function requiresFileGeneration(): bool
    {
        return match ($this) {
            self::ACH,
            self::CHECK => true,
            self::WIRE,
            self::RTGS,
            self::VIRTUAL_CARD,
            self::BOOK_TRANSFER => false,
        };
    }
}
