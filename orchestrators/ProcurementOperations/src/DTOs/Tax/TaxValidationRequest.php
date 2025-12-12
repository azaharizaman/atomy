<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;

/**
 * Request DTO for invoice tax validation.
 */
final readonly class TaxValidationRequest
{
    /**
     * @param array<TaxLineItem> $taxLines Individual tax line items
     * @param array<string, mixed> $metadata Additional validation context
     */
    public function __construct(
        public string $tenantId,
        public string $invoiceId,
        public string $vendorId,
        public Money $grossAmount,
        public Money $netAmount,
        public Money $totalTax,
        public array $taxLines,
        public string $vendorCountry,
        public string $buyerCountry,
        public ?string $vendorTaxRegistration,
        public ?string $buyerTaxRegistration,
        public \DateTimeImmutable $invoiceDate,
        public string $currencyCode,
        public array $metadata = [],
    ) {}

    /**
     * Create request for domestic purchase.
     *
     * @param array<TaxLineItem> $taxLines
     */
    public static function forDomesticPurchase(
        string $tenantId,
        string $invoiceId,
        string $vendorId,
        Money $grossAmount,
        Money $netAmount,
        Money $totalTax,
        array $taxLines,
        string $country,
        ?string $vendorTaxRegistration,
        ?string $buyerTaxRegistration,
        \DateTimeImmutable $invoiceDate,
    ): self {
        return new self(
            tenantId: $tenantId,
            invoiceId: $invoiceId,
            vendorId: $vendorId,
            grossAmount: $grossAmount,
            netAmount: $netAmount,
            totalTax: $totalTax,
            taxLines: $taxLines,
            vendorCountry: $country,
            buyerCountry: $country,
            vendorTaxRegistration: $vendorTaxRegistration,
            buyerTaxRegistration: $buyerTaxRegistration,
            invoiceDate: $invoiceDate,
            currencyCode: $grossAmount->getCurrency(),
            metadata: ['transaction_type' => 'domestic'],
        );
    }

    /**
     * Create request for cross-border purchase.
     *
     * @param array<TaxLineItem> $taxLines
     */
    public static function forCrossBorderPurchase(
        string $tenantId,
        string $invoiceId,
        string $vendorId,
        Money $grossAmount,
        Money $netAmount,
        Money $totalTax,
        array $taxLines,
        string $vendorCountry,
        string $buyerCountry,
        ?string $vendorTaxRegistration,
        ?string $buyerTaxRegistration,
        \DateTimeImmutable $invoiceDate,
    ): self {
        return new self(
            tenantId: $tenantId,
            invoiceId: $invoiceId,
            vendorId: $vendorId,
            grossAmount: $grossAmount,
            netAmount: $netAmount,
            totalTax: $totalTax,
            taxLines: $taxLines,
            vendorCountry: $vendorCountry,
            buyerCountry: $buyerCountry,
            vendorTaxRegistration: $vendorTaxRegistration,
            buyerTaxRegistration: $buyerTaxRegistration,
            invoiceDate: $invoiceDate,
            currencyCode: $grossAmount->getCurrency(),
            metadata: ['transaction_type' => 'cross_border'],
        );
    }

    /**
     * Check if this is a cross-border transaction.
     */
    public function isCrossBorder(): bool
    {
        return $this->vendorCountry !== $this->buyerCountry;
    }

    /**
     * Get the tax percentage.
     */
    public function getTaxPercentage(): float
    {
        if ($this->netAmount->isZero()) {
            return 0.0;
        }

        return ($this->totalTax->getAmount() / $this->netAmount->getAmount()) * 100;
    }
}
