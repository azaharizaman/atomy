<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\ValueObjects;

use Nexus\ProcurementOperations\Enums\PositivePayFormat;

/**
 * Configuration for Positive Pay file generation.
 *
 * Encapsulates all settings required to generate Positive Pay files
 * for check fraud prevention.
 */
final readonly class PositivePayConfiguration
{
    /**
     * @param string $bankAccountNumber Bank account number
     * @param string $bankRoutingNumber Bank routing number (9 digits)
     * @param PositivePayFormat $format Positive Pay format variant
     * @param string $companyName Company name for file header
     * @param string|null $companyId Company identification (for some formats)
     * @param bool $includeVoidedChecks Whether to include voided check records
     * @param bool $includeStopPayments Whether to include stop payment records
     * @param int $checkNumberPadding Zero-padding for check numbers
     * @param int $amountDecimalPlaces Decimal places for amounts
     * @param string $fieldDelimiter Delimiter for CSV formats
     * @param string $recordTerminator Line ending character(s)
     */
    public function __construct(
        public string $bankAccountNumber,
        public string $bankRoutingNumber,
        public PositivePayFormat $format = PositivePayFormat::STANDARD_CSV,
        public string $companyName = '',
        public ?string $companyId = null,
        public bool $includeVoidedChecks = true,
        public bool $includeStopPayments = true,
        public int $checkNumberPadding = 10,
        public int $amountDecimalPlaces = 2,
        public string $fieldDelimiter = ',',
        public string $recordTerminator = "\n",
    ) {}

    /**
     * Validate the configuration.
     *
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        // Bank account number validation
        if (empty($this->bankAccountNumber)) {
            $errors[] = 'Bank account number is required';
        } elseif (strlen($this->bankAccountNumber) > 17) {
            $errors[] = 'Bank account number cannot exceed 17 characters';
        }

        // Routing number validation
        if (!preg_match('/^\d{9}$/', $this->bankRoutingNumber)) {
            $errors[] = 'Bank routing number must be exactly 9 digits';
        } elseif (!$this->validateRoutingNumber($this->bankRoutingNumber)) {
            $errors[] = 'Bank routing number fails checksum validation';
        }

        // Check number padding validation
        if ($this->checkNumberPadding < 1 || $this->checkNumberPadding > 15) {
            $errors[] = 'Check number padding must be between 1 and 15';
        }

        // Amount decimal places validation
        if ($this->amountDecimalPlaces < 0 || $this->amountDecimalPlaces > 4) {
            $errors[] = 'Amount decimal places must be between 0 and 4';
        }

        return $errors;
    }

    /**
     * Check if configuration is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Validate a routing number using the checksum algorithm.
     */
    private function validateRoutingNumber(string $routingNumber): bool
    {
        if (!preg_match('/^\d{9}$/', $routingNumber)) {
            return false;
        }

        $weights = [3, 7, 1, 3, 7, 1, 3, 7, 1];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $routingNumber[$i] * $weights[$i];
        }

        return $sum % 10 === 0;
    }

    /**
     * Create configuration for standard CSV format.
     *
     * @param string $accountNumber Bank account number
     * @param string $routingNumber Bank routing number (9 digits)
     * @param string $name Company name for file header
     */
    public static function standardCsv(
        string $accountNumber,
        string $routingNumber,
        string $name = '',
    ): self {
        return new self(
            bankAccountNumber: $accountNumber,
            bankRoutingNumber: $routingNumber,
            format: PositivePayFormat::STANDARD_CSV,
            companyName: $name,
        );
    }

    /**
     * Create configuration for BAI2 format.
     *
     * @param string $accountNumber Bank account number
     * @param string $routingNumber Bank routing number (9 digits)
     * @param string $name Company name for file header
     * @param string $id Company identification
     */
    public static function bai2(
        string $accountNumber,
        string $routingNumber,
        string $name,
        string $id,
    ): self {
        return new self(
            bankAccountNumber: $accountNumber,
            bankRoutingNumber: $routingNumber,
            format: PositivePayFormat::BAI2,
            companyName: $name,
            companyId: $id,
        );
    }

    /**
     * Create configuration for Bank of America format.
     *
     * @param string $accountNumber Bank account number
     * @param string $routingNumber Bank routing number (9 digits)
     * @param string $name Company name for file header
     * @param string $id Company identification
     */
    public static function bankOfAmerica(
        string $accountNumber,
        string $routingNumber,
        string $name,
        string $id,
    ): self {
        return new self(
            bankAccountNumber: $accountNumber,
            bankRoutingNumber: $routingNumber,
            format: PositivePayFormat::BANK_OF_AMERICA,
            companyName: $name,
            companyId: $id,
            checkNumberPadding: 10,
        );
    }

    /**
     * Create configuration for Wells Fargo format.
     *
     * @param string $accountNumber Bank account number
     * @param string $routingNumber Bank routing number (9 digits)
     * @param string $name Company name for file header
     * @param string $id Company identification
     */
    public static function wellsFargo(
        string $accountNumber,
        string $routingNumber,
        string $name,
        string $id,
    ): self {
        return new self(
            bankAccountNumber: $accountNumber,
            bankRoutingNumber: $routingNumber,
            format: PositivePayFormat::WELLS_FARGO,
            companyName: $name,
            companyId: $id,
            checkNumberPadding: 10,
        );
    }

    /**
     * Create configuration for Chase format.
     *
     * @param string $accountNumber Bank account number
     * @param string $routingNumber Bank routing number (9 digits)
     * @param string $name Company name for file header
     * @param string $id Company identification
     */
    public static function chase(
        string $accountNumber,
        string $routingNumber,
        string $name,
        string $id,
    ): self {
        return new self(
            bankAccountNumber: $accountNumber,
            bankRoutingNumber: $routingNumber,
            format: PositivePayFormat::CHASE,
            companyName: $name,
            companyId: $id,
            checkNumberPadding: 8,
        );
    }

    /**
     * Create configuration for Citi format.
     *
     * @param string $accountNumber Bank account number
     * @param string $routingNumber Bank routing number (9 digits)
     * @param string $name Company name for file header
     * @param string $id Company identification
     */
    public static function citi(
        string $accountNumber,
        string $routingNumber,
        string $name,
        string $id,
    ): self {
        return new self(
            bankAccountNumber: $accountNumber,
            bankRoutingNumber: $routingNumber,
            format: PositivePayFormat::CITI,
            companyName: $name,
            companyId: $id,
            checkNumberPadding: 10,
        );
    }

    /**
     * Get formatted bank account number.
     */
    public function getFormattedAccountNumber(): string
    {
        return match ($this->format) {
            PositivePayFormat::BANK_OF_AMERICA => str_pad($this->bankAccountNumber, 12, '0', STR_PAD_LEFT),
            PositivePayFormat::WELLS_FARGO => str_pad($this->bankAccountNumber, 15, '0', STR_PAD_LEFT),
            default => $this->bankAccountNumber,
        };
    }

    /**
     * Format a check number according to configuration.
     */
    public function formatCheckNumber(string $checkNumber): string
    {
        // Remove any non-numeric characters
        $numeric = preg_replace('/[^0-9]/', '', $checkNumber) ?? '';

        return str_pad($numeric, $this->checkNumberPadding, '0', STR_PAD_LEFT);
    }

    /**
     * Format an amount according to configuration.
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, $this->amountDecimalPlaces, '.', '');
    }

    /**
     * Format a date according to the format's requirements.
     */
    public function formatDate(\DateTimeImmutable $date): string
    {
        return $date->format($this->format->dateFormat());
    }
}
