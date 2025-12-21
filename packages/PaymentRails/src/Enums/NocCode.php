<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * ACH Notification of Change (NOC) Codes.
 *
 * NOC codes indicate the type of information that needs to be
 * corrected for future ACH entries.
 *
 * @see https://www.nacha.org/content/notification-change-noc
 */
enum NocCode: string
{
    /**
     * Incorrect DFI Account Number.
     */
    case C01 = 'C01';

    /**
     * Incorrect Routing Number.
     */
    case C02 = 'C02';

    /**
     * Incorrect Routing Number and DFI Account Number.
     */
    case C03 = 'C03';

    /**
     * Incorrect Individual Name/Receiving Company Name.
     */
    case C04 = 'C04';

    /**
     * Incorrect Transaction Code.
     */
    case C05 = 'C05';

    /**
     * Incorrect DFI Account Number and Transaction Code.
     */
    case C06 = 'C06';

    /**
     * Incorrect Routing Number, DFI Account Number, and Transaction Code.
     */
    case C07 = 'C07';

    /**
     * Incorrect Routing Number (IAT only).
     */
    case C08 = 'C08';

    /**
     * Incorrect Individual Identification Number.
     */
    case C09 = 'C09';

    /**
     * Incorrect Company Name.
     */
    case C10 = 'C10';

    /**
     * Incorrect Company Identification.
     */
    case C11 = 'C11';

    /**
     * Incorrect Company Name and Company Identification.
     */
    case C12 = 'C12';

    /**
     * Addenda Format Error.
     */
    case C13 = 'C13';

    /**
     * Incorrect SEC Code for Outbound IAT Entry.
     */
    case C14 = 'C14';

    /**
     * Get the description for this NOC code.
     */
    public function description(): string
    {
        return match ($this) {
            self::C01 => 'Incorrect DFI Account Number',
            self::C02 => 'Incorrect Routing Number',
            self::C03 => 'Incorrect Routing Number and DFI Account Number',
            self::C04 => 'Incorrect Individual Name/Receiving Company Name',
            self::C05 => 'Incorrect Transaction Code',
            self::C06 => 'Incorrect DFI Account Number and Transaction Code',
            self::C07 => 'Incorrect Routing Number, DFI Account Number, and Transaction Code',
            self::C08 => 'Incorrect Routing Number (IAT)',
            self::C09 => 'Incorrect Individual Identification Number',
            self::C10 => 'Incorrect Company Name',
            self::C11 => 'Incorrect Company Identification',
            self::C12 => 'Incorrect Company Name and Company Identification',
            self::C13 => 'Addenda Format Error',
            self::C14 => 'Incorrect SEC Code for Outbound IAT Entry',
        };
    }

    /**
     * Get the fields that need to be updated based on this NOC.
     *
     * @return array<string>
     */
    public function fieldsToUpdate(): array
    {
        return match ($this) {
            self::C01 => ['account_number'],
            self::C02 => ['routing_number'],
            self::C03 => ['routing_number', 'account_number'],
            self::C04 => ['individual_name'],
            self::C05 => ['transaction_code'],
            self::C06 => ['account_number', 'transaction_code'],
            self::C07 => ['routing_number', 'account_number', 'transaction_code'],
            self::C08 => ['routing_number'],
            self::C09 => ['individual_id'],
            self::C10 => ['company_name'],
            self::C11 => ['company_id'],
            self::C12 => ['company_name', 'company_id'],
            self::C13 => ['addenda'],
            self::C14 => ['sec_code'],
        };
    }

    /**
     * Check if this NOC affects account information.
     */
    public function affectsAccountInfo(): bool
    {
        return match ($this) {
            self::C01, self::C02, self::C03, self::C05, self::C06, self::C07, self::C08 => true,
            default => false,
        };
    }

    /**
     * Check if this NOC affects company information.
     */
    public function affectsCompanyInfo(): bool
    {
        return match ($this) {
            self::C10, self::C11, self::C12 => true,
            default => false,
        };
    }

    /**
     * Check if this is an IAT-specific NOC.
     */
    public function isIatSpecific(): bool
    {
        return match ($this) {
            self::C08, self::C14 => true,
            default => false,
        };
    }
}
