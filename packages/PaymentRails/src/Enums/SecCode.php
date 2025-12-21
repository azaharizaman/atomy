<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Standard Entry Class (SEC) codes for ACH transactions.
 *
 * SEC codes identify the type of ACH transaction and determine
 * the rules and requirements that apply to the entry.
 *
 * @see https://www.nacha.org/rules
 */
enum SecCode: string
{
    /**
     * Prearranged Payment and Deposit.
     * Consumer transactions with prior written authorization.
     */
    case PPD = 'PPD';

    /**
     * Corporate Credit or Debit.
     * B2B transactions between companies.
     */
    case CCD = 'CCD';

    /**
     * Internet-Initiated Entry.
     * Consumer transactions initiated via internet.
     */
    case WEB = 'WEB';

    /**
     * Telephone-Initiated Entry.
     * Consumer transactions initiated via telephone.
     */
    case TEL = 'TEL';

    /**
     * Corporate Trade Exchange.
     * B2B transactions with addenda records for remittance.
     */
    case CTX = 'CTX';

    /**
     * International ACH Transaction.
     * Cross-border ACH transactions.
     */
    case IAT = 'IAT';

    /**
     * Point-of-Purchase.
     * In-person consumer transactions at point of sale.
     */
    case POP = 'POP';

    /**
     * Accounts Receivable Entry.
     * Converting paper checks to ACH debits.
     */
    case ARC = 'ARC';

    /**
     * Back Office Conversion.
     * Converting checks received at lockbox.
     */
    case BOC = 'BOC';

    /**
     * Re-presented Check Entry.
     * Re-presenting returned checks electronically.
     */
    case RCK = 'RCK';

    /**
     * Get a human-readable description of the SEC code.
     */
    public function description(): string
    {
        return match ($this) {
            self::PPD => 'Prearranged Payment and Deposit',
            self::CCD => 'Corporate Credit or Debit',
            self::WEB => 'Internet-Initiated Entry',
            self::TEL => 'Telephone-Initiated Entry',
            self::CTX => 'Corporate Trade Exchange',
            self::IAT => 'International ACH Transaction',
            self::POP => 'Point-of-Purchase',
            self::ARC => 'Accounts Receivable Entry',
            self::BOC => 'Back Office Conversion',
            self::RCK => 'Re-presented Check Entry',
        };
    }

    /**
     * Check if this SEC code is for consumer transactions.
     */
    public function isConsumer(): bool
    {
        return match ($this) {
            self::PPD,
            self::WEB,
            self::TEL,
            self::POP,
            self::ARC,
            self::BOC,
            self::RCK => true,
            self::CCD,
            self::CTX,
            self::IAT => false,
        };
    }

    /**
     * Check if this SEC code is for corporate/B2B transactions.
     */
    public function isCorporate(): bool
    {
        return match ($this) {
            self::CCD,
            self::CTX => true,
            self::PPD,
            self::WEB,
            self::TEL,
            self::IAT,
            self::POP,
            self::ARC,
            self::BOC,
            self::RCK => false,
        };
    }

    /**
     * Check if this SEC code supports addenda records.
     */
    public function supportsAddenda(): bool
    {
        return match ($this) {
            self::CCD,
            self::CTX,
            self::PPD,
            self::WEB,
            self::IAT => true,
            self::TEL,
            self::POP,
            self::ARC,
            self::BOC,
            self::RCK => false,
        };
    }

    /**
     * Check if this SEC code requires written authorization.
     */
    public function requiresWrittenAuth(): bool
    {
        return match ($this) {
            self::PPD,
            self::CCD,
            self::CTX => true,
            self::WEB,
            self::TEL,
            self::IAT,
            self::POP,
            self::ARC,
            self::BOC,
            self::RCK => false,
        };
    }

    /**
     * Check if this SEC code supports debit transactions.
     */
    public function supportsDebit(): bool
    {
        return match ($this) {
            self::PPD,
            self::CCD,
            self::WEB,
            self::TEL,
            self::CTX,
            self::IAT,
            self::POP,
            self::ARC,
            self::BOC,
            self::RCK => true,
        };
    }

    /**
     * Check if this SEC code supports credit transactions.
     */
    public function supportsCredit(): bool
    {
        return match ($this) {
            self::PPD,
            self::CCD,
            self::CTX,
            self::IAT => true,
            self::WEB,
            self::TEL,
            self::POP,
            self::ARC,
            self::BOC,
            self::RCK => false,
        };
    }
}
