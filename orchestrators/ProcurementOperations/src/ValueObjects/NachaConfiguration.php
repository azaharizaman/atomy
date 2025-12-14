<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\ValueObjects;

use Nexus\ProcurementOperations\Enums\NachaSecCode;

/**
 * Configuration for NACHA ACH file generation.
 *
 * Encapsulates all the originator and file-level settings required
 * to generate a NACHA-compliant ACH file.
 */
final readonly class NachaConfiguration
{
    /**
     * @param string $immediateDestination Receiving bank routing number (9 digits)
     * @param string $immediateOrigin Originating bank routing number (9 digits)
     * @param string $immediateDestinationName Name of receiving bank (up to 23 chars)
     * @param string $immediateOriginName Name of originating company (up to 23 chars)
     * @param string $companyName Company name for batch header (up to 16 chars)
     * @param string $companyId Company identification (10 chars, typically 1 + 9-digit tax ID)
     * @param NachaSecCode $secCode Standard Entry Class code
     * @param string $entryDescription Entry description (up to 10 chars)
     * @param string $discretionaryData Optional company discretionary data (up to 20 chars)
     * @param string $referenceCode Optional reference code for File Header (up to 8 chars)
     * @param bool $balancedFile Whether to generate a balanced file with offsetting entry
     * @param string|null $offsetAccountNumber Account for offsetting entry if balanced
     * @param string|null $offsetRoutingNumber Routing for offsetting entry if balanced
     */
    public function __construct(
        public string $immediateDestination,
        public string $immediateOrigin,
        public string $immediateDestinationName,
        public string $immediateOriginName,
        public string $companyName,
        public string $companyId,
        public NachaSecCode $secCode = NachaSecCode::CCD,
        public string $entryDescription = 'PAYMENT',
        public string $discretionaryData = '',
        public string $referenceCode = '',
        public bool $balancedFile = false,
        public ?string $offsetAccountNumber = null,
        public ?string $offsetRoutingNumber = null,
    ) {}

    /**
     * Validate the configuration.
     *
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        // Immediate Destination validation
        $dest = ltrim($this->immediateDestination, ' ');
        if (!preg_match('/^\d{9}$/', $dest)) {
            $errors[] = 'Immediate Destination must be exactly 9 digits';
        } elseif (!$this->validateRoutingNumber($dest)) {
            $errors[] = 'Immediate Destination fails routing number checksum validation';
        }

        // Immediate Origin validation
        $origin = ltrim($this->immediateOrigin, ' ');
        if (!preg_match('/^\d{9}$/', $origin) && !preg_match('/^\d{10}$/', $origin)) {
            $errors[] = 'Immediate Origin must be 9 or 10 digits';
        }

        // Company ID validation
        if (strlen($this->companyId) !== 10) {
            $errors[] = 'Company ID must be exactly 10 characters';
        }

        // Company Name validation
        if (strlen($this->companyName) < 1 || strlen($this->companyName) > 16) {
            $errors[] = 'Company Name must be 1-16 characters';
        }

        // Entry Description validation
        if (strlen($this->entryDescription) < 1 || strlen($this->entryDescription) > 10) {
            $errors[] = 'Entry Description must be 1-10 characters';
        }

        // Balanced file validation
        if ($this->balancedFile) {
            if (empty($this->offsetAccountNumber)) {
                $errors[] = 'Offset account number required for balanced files';
            }
            if (empty($this->offsetRoutingNumber)) {
                $errors[] = 'Offset routing number required for balanced files';
            } elseif (!$this->validateRoutingNumber($this->offsetRoutingNumber)) {
                $errors[] = 'Offset routing number fails checksum validation';
            }
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
     *
     * ABA routing numbers use a weighted checksum where:
     * 3(d1) + 7(d2) + 1(d3) + 3(d4) + 7(d5) + 1(d6) + 3(d7) + 7(d8) + 1(d9) ≡ 0 (mod 10)
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
     * Create configuration for vendor payments.
     */
    public static function forVendorPayments(
        string $immediateDestination,
        string $immediateOrigin,
        string $destinationName,
        string $originName,
        string $companyName,
        string $companyId,
    ): self {
        return new self(
            immediateDestination: $immediateDestination,
            immediateOrigin: $immediateOrigin,
            immediateDestinationName: $destinationName,
            immediateOriginName: $originName,
            companyName: $companyName,
            companyId: $companyId,
            secCode: NachaSecCode::CCD,
            entryDescription: 'VENDOR PMT',
        );
    }

    /**
     * Get formatted immediate destination for file header (with leading space).
     */
    public function getFormattedImmediateDestination(): string
    {
        $dest = ltrim($this->immediateDestination, ' ');

        return ' ' . str_pad($dest, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted immediate origin for file header (with leading space).
     */
    public function getFormattedImmediateOrigin(): string
    {
        $origin = ltrim($this->immediateOrigin, ' ');

        return ' ' . str_pad($origin, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted company name (uppercase, padded to 16 chars).
     */
    public function getFormattedCompanyName(): string
    {
        return str_pad(strtoupper(substr($this->companyName, 0, 16)), 16);
    }

    /**
     * Get formatted entry description (uppercase, padded to 10 chars).
     */
    public function getFormattedEntryDescription(): string
    {
        return str_pad(strtoupper(substr($this->entryDescription, 0, 10)), 10);
    }

    /**
     * Get formatted discretionary data (padded to 20 chars).
     */
    public function getFormattedDiscretionaryData(): string
    {
        return str_pad(substr($this->discretionaryData, 0, 20), 20);
    }
}
