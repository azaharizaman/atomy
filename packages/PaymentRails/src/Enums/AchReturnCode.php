<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * ACH Return Reason Codes as defined by NACHA.
 *
 * Return codes indicate why an ACH entry was returned by the
 * Receiving Depository Financial Institution (RDFI).
 *
 * @see https://www.nacha.org/content/ach-return-codes
 */
enum AchReturnCode: string
{
    // Administrative Returns (R01-R04)
    case R01 = 'R01';  // Insufficient Funds
    case R02 = 'R02';  // Account Closed
    case R03 = 'R03';  // No Account/Unable to Locate Account
    case R04 = 'R04';  // Invalid Account Number Structure

    // Authorization Returns (R05-R10)
    case R05 = 'R05';  // Unauthorized Debit to Consumer Account
    case R06 = 'R06';  // Returned per ODFI's Request
    case R07 = 'R07';  // Authorization Revoked by Customer
    case R08 = 'R08';  // Payment Stopped
    case R09 = 'R09';  // Uncollected Funds
    case R10 = 'R10';  // Customer Advises Originator Not Authorized

    // Account Returns (R11-R17)
    case R11 = 'R11';  // Check Truncation Entry Return
    case R12 = 'R12';  // Account Sold to Another DFI
    case R13 = 'R13';  // RDFI Not Qualified to Participate
    case R14 = 'R14';  // Representative Payee Deceased
    case R15 = 'R15';  // Beneficiary or Account Holder Deceased
    case R16 = 'R16';  // Account Frozen
    case R17 = 'R17';  // File Record Edit Criteria

    // Other Returns (R20-R31)
    case R20 = 'R20';  // Non-Transaction Account
    case R21 = 'R21';  // Invalid Company Identification
    case R22 = 'R22';  // Invalid Individual ID Number
    case R23 = 'R23';  // Credit Entry Refused by Receiver
    case R24 = 'R24';  // Duplicate Entry
    case R25 = 'R25';  // Addenda Error
    case R26 = 'R26';  // Mandatory Field Error
    case R27 = 'R27';  // Trace Number Error
    case R28 = 'R28';  // Routing Number Check Digit Error
    case R29 = 'R29';  // Corporate Customer Advises Not Authorized
    case R30 = 'R30';  // RDFI Not Participant in Check Truncation
    case R31 = 'R31';  // Permissible Return Entry (CCD and CTX)

    // Special Returns (R32-R39)
    case R32 = 'R32';  // RDFI Non-Settlement
    case R33 = 'R33';  // Return of XCK Entry
    case R34 = 'R34';  // Limited Participation DFI
    case R35 = 'R35';  // Return of Improper Debit Entry
    case R36 = 'R36';  // Return of Improper Credit Entry
    case R37 = 'R37';  // Source Document Presented for Payment
    case R38 = 'R38';  // Stop Payment on Source Document
    case R39 = 'R39';  // Improper Source Document

    // International Returns (R61-R69)
    case R61 = 'R61';  // Misrouted Return
    case R62 = 'R62';  // Return of Erroneous or Reversing Debit
    case R63 = 'R63';  // Incorrect Dollar Amount
    case R64 = 'R64';  // Incorrect Individual Identification
    case R65 = 'R65';  // Incorrect Transaction Code
    case R66 = 'R66';  // Incorrect Company Identification
    case R67 = 'R67';  // Duplicate Return
    case R68 = 'R68';  // Untimely Return
    case R69 = 'R69';  // Multiple Errors

    // Dishonored Returns (R70-R85)
    case R70 = 'R70';  // Permissible Return Entry Not Accepted
    case R71 = 'R71';  // Misrouted Dishonored Return
    case R72 = 'R72';  // Untimely Dishonored Return
    case R73 = 'R73';  // Timely Original Return
    case R74 = 'R74';  // Corrected Return
    case R75 = 'R75';  // Return Not a Duplicate
    case R76 = 'R76';  // No Errors Found
    case R80 = 'R80';  // IAT Entry Coding Error
    case R81 = 'R81';  // Non-Participant in IAT Program
    case R82 = 'R82';  // Invalid Foreign RDFI Identification
    case R83 = 'R83';  // Foreign RDFI Unable to Settle
    case R84 = 'R84';  // Entry Not Processed by Gateway
    case R85 = 'R85';  // Incorrectly Coded Outbound International Payment

    /**
     * Get the description for this return code.
     */
    public function description(): string
    {
        return match ($this) {
            self::R01 => 'Insufficient Funds',
            self::R02 => 'Account Closed',
            self::R03 => 'No Account/Unable to Locate Account',
            self::R04 => 'Invalid Account Number Structure',
            self::R05 => 'Unauthorized Debit to Consumer Account Using Corporate SEC Code',
            self::R06 => 'Returned per ODFI\'s Request',
            self::R07 => 'Authorization Revoked by Customer',
            self::R08 => 'Payment Stopped',
            self::R09 => 'Uncollected Funds',
            self::R10 => 'Customer Advises Originator is Not Known to Receiver and/or Not Authorized',
            self::R11 => 'Check Truncation Entry Return',
            self::R12 => 'Account Sold to Another DFI',
            self::R13 => 'RDFI Not Qualified to Participate',
            self::R14 => 'Representative Payee Deceased or Unable to Continue in that Capacity',
            self::R15 => 'Beneficiary or Account Holder Deceased',
            self::R16 => 'Account Frozen',
            self::R17 => 'File Record Edit Criteria',
            self::R20 => 'Non-Transaction Account',
            self::R21 => 'Invalid Company Identification',
            self::R22 => 'Invalid Individual ID Number',
            self::R23 => 'Credit Entry Refused by Receiver',
            self::R24 => 'Duplicate Entry',
            self::R25 => 'Addenda Error',
            self::R26 => 'Mandatory Field Error',
            self::R27 => 'Trace Number Error',
            self::R28 => 'Routing Number Check Digit Error',
            self::R29 => 'Corporate Customer Advises Not Authorized',
            self::R30 => 'RDFI Not Participant in Check Truncation Program',
            self::R31 => 'Permissible Return Entry (CCD and CTX Only)',
            self::R32 => 'RDFI Non-Settlement',
            self::R33 => 'Return of XCK Entry',
            self::R34 => 'Limited Participation DFI',
            self::R35 => 'Return of Improper Debit Entry',
            self::R36 => 'Return of Improper Credit Entry',
            self::R37 => 'Source Document Presented for Payment',
            self::R38 => 'Stop Payment on Source Document',
            self::R39 => 'Improper Source Document/Source Document Presented for Payment',
            self::R61 => 'Misrouted Return',
            self::R62 => 'Return of Erroneous or Reversing Debit',
            self::R63 => 'Incorrect Dollar Amount',
            self::R64 => 'Incorrect Individual Identification',
            self::R65 => 'Incorrect Transaction Code',
            self::R66 => 'Incorrect Company Identification',
            self::R67 => 'Duplicate Return',
            self::R68 => 'Untimely Return',
            self::R69 => 'Multiple Errors',
            self::R70 => 'Permissible Return Entry Not Accepted',
            self::R71 => 'Misrouted Dishonored Return',
            self::R72 => 'Untimely Dishonored Return',
            self::R73 => 'Timely Original Return',
            self::R74 => 'Corrected Return',
            self::R75 => 'Return Not a Duplicate',
            self::R76 => 'No Errors Found',
            self::R80 => 'IAT Entry Coding Error',
            self::R81 => 'Non-Participant in IAT Program',
            self::R82 => 'Invalid Foreign Receiving DFI Identification',
            self::R83 => 'Foreign Receiving DFI Unable to Settle',
            self::R84 => 'Entry Not Processed by Gateway',
            self::R85 => 'Incorrectly Coded Outbound International Payment',
        };
    }

    /**
     * Check if this is an administrative return (account issue).
     */
    public function isAdministrative(): bool
    {
        return match ($this) {
            self::R01, self::R02, self::R03, self::R04 => true,
            default => false,
        };
    }

    /**
     * Check if this return is due to insufficient funds.
     */
    public function isInsufficientFunds(): bool
    {
        return match ($this) {
            self::R01, self::R09 => true,
            default => false,
        };
    }

    /**
     * Check if this return is due to authorization issues.
     */
    public function isAuthorizationIssue(): bool
    {
        return match ($this) {
            self::R05, self::R07, self::R08, self::R10, self::R29 => true,
            default => false,
        };
    }

    /**
     * Check if the account info needs to be updated.
     */
    public function requiresAccountUpdate(): bool
    {
        return match ($this) {
            self::R02, self::R03, self::R04, self::R12 => true,
            default => false,
        };
    }

    /**
     * Check if this return is retriable.
     */
    public function isRetriable(): bool
    {
        return match ($this) {
            self::R01, self::R09 => true,
            default => false,
        };
    }

    /**
     * Get the suggested action for this return code.
     */
    public function suggestedAction(): string
    {
        return match ($this) {
            self::R01, self::R09 => 'Retry payment after sufficient funds are available',
            self::R02, self::R03 => 'Contact customer for updated account information',
            self::R04, self::R28 => 'Verify and correct account/routing number',
            self::R05, self::R07, self::R10, self::R29 => 'Obtain new authorization from customer',
            self::R08 => 'Contact customer - payment was stopped',
            self::R12 => 'Request updated account information',
            self::R15 => 'Contact estate or authorized representative',
            self::R16 => 'Account is frozen - contact customer',
            self::R24 => 'Check for duplicate submission',
            default => 'Review and resolve based on specific circumstances',
        };
    }
}
