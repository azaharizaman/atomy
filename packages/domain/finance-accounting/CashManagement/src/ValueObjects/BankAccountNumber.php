<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

use InvalidArgumentException;

/**
 * Bank Account Number Value Object
 *
 * Immutable representation of a bank account number with validation.
 */
final readonly class BankAccountNumber
{
    public function __construct(
        private string $accountNumber,
        private string $bankCode,
        private ?string $branchCode = null,
        private ?string $swiftCode = null,
        private ?string $iban = null
    ) {
        $this->validate();
    }

    /**
     * Validate bank account number
     */
    private function validate(): void
    {
        if (empty($this->accountNumber)) {
            throw new InvalidArgumentException('Account number cannot be empty');
        }

        if (empty($this->bankCode)) {
            throw new InvalidArgumentException('Bank code cannot be empty');
        }

        // Validate IBAN format if provided
        if ($this->iban !== null && !$this->isValidIban($this->iban)) {
            throw new InvalidArgumentException('Invalid IBAN format');
        }

        // Validate SWIFT code format if provided
        if ($this->swiftCode !== null && !$this->isValidSwiftCode($this->swiftCode)) {
            throw new InvalidArgumentException('Invalid SWIFT code format');
        }
    }

    /**
     * Validate IBAN format (basic check)
     */
    private function isValidIban(string $iban): bool
    {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(str_replace(' ', '', $iban));
        
        // Basic length check (15-34 characters)
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // Must start with 2 letter country code
        return preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban) === 1;
    }

    /**
     * Validate SWIFT code format
     */
    private function isValidSwiftCode(string $swift): bool
    {
        // SWIFT code: 8 or 11 characters
        $swift = strtoupper(str_replace(' ', '', $swift));
        return preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $swift) === 1;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function getBankCode(): string
    {
        return $this->bankCode;
    }

    public function getBranchCode(): ?string
    {
        return $this->branchCode;
    }

    public function getSwiftCode(): ?string
    {
        return $this->swiftCode;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * Get formatted account number (masked for security)
     */
    public function getMasked(): string
    {
        $length = strlen($this->accountNumber);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($this->accountNumber, -4);
    }

    /**
     * Convert to string representation
     */
    public function toString(): string
    {
        $parts = [$this->bankCode];
        
        if ($this->branchCode !== null) {
            $parts[] = $this->branchCode;
        }
        
        $parts[] = $this->accountNumber;
        
        return implode('-', $parts);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
