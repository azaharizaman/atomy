<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * NACHA Standard Entry Class (SEC) Codes.
 *
 * SEC codes identify the type of ACH transaction and determine
 * the rights and obligations of all parties involved.
 *
 * @see https://www.nacha.org/rules/standard-entry-class-sec-codes
 */
enum NachaSecCode: string
{
    /**
     * Corporate Credit or Debit Entry.
     * Used for B2B payments between corporate entities.
     * Requires written agreement between originator and receiver.
     */
    case CCD = 'CCD';

    /**
     * Corporate Trade Exchange.
     * B2B payment with up to 9,999 addenda records for remittance information.
     * Used for complex payments with detailed invoice data.
     */
    case CTX = 'CTX';

    /**
     * Prearranged Payment and Deposit Entry.
     * Used for payroll direct deposits and consumer bill payments.
     * Requires authorization from the consumer.
     */
    case PPD = 'PPD';

    /**
     * Internet-Initiated Entry.
     * For payments authorized via the internet.
     * Requires specific authentication and authorization.
     */
    case WEB = 'WEB';

    /**
     * Telephone-Initiated Entry.
     * For payments authorized via telephone.
     * Requires recorded verbal authorization.
     */
    case TEL = 'TEL';

    /**
     * Point-of-Purchase Entry.
     * Consumer check conversion at point of sale.
     */
    case POP = 'POP';

    /**
     * Accounts Receivable Entry.
     * Consumer check conversion for accounts receivable.
     */
    case ARC = 'ARC';

    /**
     * Back Office Conversion Entry.
     * Check conversion in back office.
     */
    case BOC = 'BOC';

    /**
     * Get human-readable description.
     */
    public function description(): string
    {
        return match ($this) {
            self::CCD => 'Corporate Credit or Debit Entry',
            self::CTX => 'Corporate Trade Exchange',
            self::PPD => 'Prearranged Payment and Deposit Entry',
            self::WEB => 'Internet-Initiated Entry',
            self::TEL => 'Telephone-Initiated Entry',
            self::POP => 'Point-of-Purchase Entry',
            self::ARC => 'Accounts Receivable Entry',
            self::BOC => 'Back Office Conversion Entry',
        };
    }

    /**
     * Check if this SEC code is for B2B (business-to-business) transactions.
     */
    public function isB2B(): bool
    {
        return match ($this) {
            self::CCD, self::CTX => true,
            default => false,
        };
    }

    /**
     * Check if this SEC code is for consumer transactions.
     */
    public function isConsumer(): bool
    {
        return match ($this) {
            self::PPD, self::WEB, self::TEL, self::POP, self::ARC, self::BOC => true,
            default => false,
        };
    }

    /**
     * Check if this SEC code supports addenda records.
     */
    public function supportsAddenda(): bool
    {
        return match ($this) {
            self::CCD, self::CTX, self::PPD, self::WEB => true,
            default => false,
        };
    }

    /**
     * Get default SEC code for vendor payments.
     */
    public static function defaultForVendorPayment(): self
    {
        return self::CCD;
    }
}
