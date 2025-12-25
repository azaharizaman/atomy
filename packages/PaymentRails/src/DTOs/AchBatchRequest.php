<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;

/**
 * Request DTO for creating an ACH batch.
 */
final readonly class AchBatchRequest
{
    /**
     * @param string $companyName Company/originator name
     * @param string $companyId Company identification (10 chars max)
     * @param string $companyEntryDescription Entry description (10 chars max)
     * @param RoutingNumber $originatingDfi Originating bank routing number
     * @param SecCode $secCode Standard Entry Class code
     * @param array<AchEntryRequest> $entries Individual ACH entries
     * @param string|null $companyDiscretionaryData Optional discretionary data
     * @param \DateTimeImmutable|null $effectiveEntryDate Effective date (defaults to next business day)
     * @param bool $isSameDay Whether to use same-day ACH
     * @param string|null $batchId Optional external batch identifier
     */
    public function __construct(
        public string $companyName,
        public string $companyId,
        public string $companyEntryDescription,
        public RoutingNumber $originatingDfi,
        public SecCode $secCode,
        public array $entries,
        public ?string $companyDiscretionaryData = null,
        public ?\DateTimeImmutable $effectiveEntryDate = null,
        public bool $isSameDay = false,
        public ?string $batchId = null,
    ) {}

    /**
     * Create a PPD batch request for payroll/direct deposit.
     *
     * @param array<AchEntryRequest> $entries
     */
    public static function payroll(
        string $companyName,
        string $companyId,
        RoutingNumber $originatingDfi,
        array $entries,
        ?\DateTimeImmutable $effectiveEntryDate = null,
    ): self {
        return new self(
            companyName: $companyName,
            companyId: $companyId,
            companyEntryDescription: 'PAYROLL',
            originatingDfi: $originatingDfi,
            secCode: SecCode::PPD,
            entries: $entries,
            effectiveEntryDate: $effectiveEntryDate,
        );
    }

    /**
     * Create a CCD batch request for vendor payments.
     *
     * @param array<AchEntryRequest> $entries
     */
    public static function vendorPayment(
        string $companyName,
        string $companyId,
        RoutingNumber $originatingDfi,
        array $entries,
        ?\DateTimeImmutable $effectiveEntryDate = null,
    ): self {
        return new self(
            companyName: $companyName,
            companyId: $companyId,
            companyEntryDescription: 'VENDOR PMT',
            originatingDfi: $originatingDfi,
            secCode: SecCode::CCD,
            entries: $entries,
            effectiveEntryDate: $effectiveEntryDate,
        );
    }

    /**
     * Create a WEB batch request for web-authorized debits.
     *
     * @param array<AchEntryRequest> $entries
     */
    public static function webDebit(
        string $companyName,
        string $companyId,
        RoutingNumber $originatingDfi,
        array $entries,
        ?\DateTimeImmutable $effectiveEntryDate = null,
    ): self {
        return new self(
            companyName: $companyName,
            companyId: $companyId,
            companyEntryDescription: 'WEB DEBIT',
            originatingDfi: $originatingDfi,
            secCode: SecCode::WEB,
            entries: $entries,
            effectiveEntryDate: $effectiveEntryDate,
        );
    }

    /**
     * Get the total number of entries.
     */
    public function getEntryCount(): int
    {
        return count($this->entries);
    }

    /**
     * Calculate total debit amount.
     */
    public function getTotalDebits(): Money
    {
        $total = Money::zero('USD');

        foreach ($this->entries as $entry) {
            if ($entry->isDebit) {
                $total = $total->add($entry->amount);
            }
        }

        return $total;
    }

    /**
     * Calculate total credit amount.
     */
    public function getTotalCredits(): Money
    {
        $total = Money::zero('USD');

        foreach ($this->entries as $entry) {
            if (!$entry->isDebit) {
                $total = $total->add($entry->amount);
            }
        }

        return $total;
    }

    /**
     * Validate the batch request.
     *
     * @return array<string> Validation errors
     */
    public function validate(): array
    {
        $errors = [];

        if (mb_strlen($this->companyName) > 16) {
            $errors[] = 'Company name must not exceed 16 characters';
        }

        if (mb_strlen($this->companyId) > 10) {
            $errors[] = 'Company ID must not exceed 10 characters';
        }

        if (mb_strlen($this->companyEntryDescription) > 10) {
            $errors[] = 'Entry description must not exceed 10 characters';
        }

        if (empty($this->entries)) {
            $errors[] = 'Batch must contain at least one entry';
        }

        if (count($this->entries) > 9999) {
            $errors[] = 'Batch cannot exceed 9999 entries';
        }

        foreach ($this->entries as $index => $entry) {
            $entryErrors = $entry->validate();
            foreach ($entryErrors as $error) {
                $errors[] = "Entry {$index}: {$error}";
            }
        }

        return $errors;
    }
}
