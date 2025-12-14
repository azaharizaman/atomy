<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\ValueObjects;

/**
 * Configuration for SWIFT MT101 file generation.
 *
 * Contains all necessary parameters for generating SWIFT MT101 messages
 * including sender information, ordering customer details, and defaults.
 */
final readonly class SwiftMt101Configuration
{
    /**
     * @param string $senderBic Bank Identifier Code of the sending institution (8 or 11 chars)
     * @param string $orderingCustomerAccount Ordering customer's account number
     * @param string $orderingCustomerName Ordering customer's name (company name)
     * @param string|null $accountServicingInstitution BIC of account servicing institution (optional)
     * @param string $defaultChargeCode Default charge code: SHA (shared), OUR, BEN
     * @param string|null $orderingCustomerAddress Ordering customer address (optional)
     * @param string|null $orderingCustomerCountry Ordering customer country code (optional)
     */
    public function __construct(
        public string $senderBic,
        public string $orderingCustomerAccount,
        public string $orderingCustomerName,
        public ?string $accountServicingInstitution = null,
        public string $defaultChargeCode = 'SHA',
        public ?string $orderingCustomerAddress = null,
        public ?string $orderingCustomerCountry = null,
    ) {}

    /**
     * Validate the configuration.
     */
    public function isValid(): bool
    {
        // Validate sender BIC format (8 or 11 characters)
        if (!$this->isValidBic($this->senderBic)) {
            return false;
        }

        // Account number required
        if (empty($this->orderingCustomerAccount)) {
            return false;
        }

        // Customer name required
        if (empty($this->orderingCustomerName)) {
            return false;
        }

        // Validate charge code
        if (!in_array($this->defaultChargeCode, ['SHA', 'OUR', 'BEN'], true)) {
            return false;
        }

        // Validate account servicing institution BIC if provided
        if ($this->accountServicingInstitution !== null && !$this->isValidBic($this->accountServicingInstitution)) {
            return false;
        }

        return true;
    }

    /**
     * Validate BIC format.
     */
    private function isValidBic(string $bic): bool
    {
        // BIC is 8 or 11 characters
        // Format: AAAABBCCXXX
        // - AAAA: Bank code (letters)
        // - BB: Country code (letters)
        // - CC: Location code (letters/digits)
        // - XXX: Branch code (optional, letters/digits)
        return (bool) preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', strtoupper($bic));
    }

    /**
     * Get formatted sender BIC (padded to 12 characters).
     */
    public function getFormattedSenderBic(): string
    {
        return str_pad(strtoupper($this->senderBic), 12, 'X');
    }

    /**
     * Get formatted ordering customer (for SWIFT message).
     *
     * @return array<string>
     */
    public function getFormattedOrderingCustomer(): array
    {
        $lines = [];

        // Account line
        $lines[] = '/' . $this->orderingCustomerAccount;

        // Name (max 35 chars per line, up to 4 lines)
        $name = strtoupper($this->orderingCustomerName);
        if (strlen($name) > 35) {
            $nameLines = str_split($name, 35);
            $lines = array_merge($lines, array_slice($nameLines, 0, 4));
        } else {
            $lines[] = $name;
        }

        // Address if provided
        if ($this->orderingCustomerAddress !== null) {
            $address = strtoupper($this->orderingCustomerAddress);
            if (strlen($address) > 35) {
                $addressLines = str_split($address, 35);
                $lines = array_merge($lines, array_slice($addressLines, 0, 2));
            } else {
                $lines[] = $address;
            }
        }

        // Country if provided
        if ($this->orderingCustomerCountry !== null) {
            $lines[] = strtoupper($this->orderingCustomerCountry);
        }

        return array_slice($lines, 0, 6); // Max 6 lines total
    }

    /**
     * Create configuration for international vendor payments.
     */
    public static function forInternationalPayments(
        string $senderBic,
        string $accountNumber,
        string $companyName,
        ?string $accountServicingBic = null,
    ): self {
        return new self(
            senderBic: $senderBic,
            orderingCustomerAccount: $accountNumber,
            orderingCustomerName: $companyName,
            accountServicingInstitution: $accountServicingBic,
            defaultChargeCode: 'SHA', // Shared charges is most common
        );
    }

    /**
     * Create configuration where ordering party pays all charges.
     */
    public static function withOurCharges(
        string $senderBic,
        string $accountNumber,
        string $companyName,
        ?string $accountServicingBic = null,
    ): self {
        return new self(
            senderBic: $senderBic,
            orderingCustomerAccount: $accountNumber,
            orderingCustomerName: $companyName,
            accountServicingInstitution: $accountServicingBic,
            defaultChargeCode: 'OUR',
        );
    }
}
