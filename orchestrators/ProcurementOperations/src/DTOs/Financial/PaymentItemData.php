<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment Item Data
 * 
 * Represents a single payment to a vendor within a payment batch.
 */
final readonly class PaymentItemData
{
    /**
     * @param array<string> $invoiceIds
     */
    public function __construct(
        public string $paymentItemId,
        public string $vendorId,
        public string $vendorName,
        public Money $amount,
        public array $invoiceIds,
        public string $paymentReference,
        public string $status, // 'pending', 'processed', 'failed', 'reversed'
        public ?string $vendorBankAccountNumber = null,
        public ?string $vendorBankRoutingNumber = null,
        public ?string $vendorBankName = null,
        public ?string $vendorBankAccountName = null,
        public ?Money $discountTaken = null,
        public ?Money $withholdingTax = null,
        public ?Money $netAmount = null,
        public ?string $checkNumber = null,
        public ?string $wireReference = null,
        public ?string $achTraceNumber = null,
        public ?string $failureReason = null,
        public ?\DateTimeImmutable $processedAt = null,
        // Check-specific fields for Positive Pay
        public ?\DateTimeImmutable $checkDate = null,
        public ?string $checkType = null, // 'ISSUED', 'VOID', 'STOP', 'STALE'
        // International payment fields (SWIFT/Wire)
        public ?string $beneficiaryBic = null, // SWIFT BIC code (8 or 11 chars)
        public ?string $beneficiaryIban = null, // IBAN for international transfers
        public ?string $beneficiaryName = null, // Beneficiary name (for international)
        public ?string $beneficiaryAddress = null, // Beneficiary address
        public ?string $beneficiaryCountry = null, // Beneficiary country code (ISO 3166)
        public ?string $intermediaryBic = null, // Intermediary bank BIC
        public ?string $chargeCode = null, // 'SHA', 'OUR', 'BEN' for SWIFT
        public array $metadata = [],
    ) {}

    /**
     * Create payment item for ACH payment
     */
    public static function forAch(
        string $paymentItemId,
        string $vendorId,
        string $vendorName,
        Money $amount,
        array $invoiceIds,
        string $paymentReference,
        string $bankAccountNumber,
        string $routingNumber,
        string $bankName,
        string $accountName,
        ?Money $discountTaken = null,
        ?Money $withholdingTax = null,
    ): self {
        $netAmount = $amount;
        if ($discountTaken !== null) {
            $netAmount = $netAmount->subtract($discountTaken);
        }
        if ($withholdingTax !== null) {
            $netAmount = $netAmount->subtract($withholdingTax);
        }

        return new self(
            paymentItemId: $paymentItemId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            amount: $amount,
            invoiceIds: $invoiceIds,
            paymentReference: $paymentReference,
            status: 'pending',
            vendorBankAccountNumber: $bankAccountNumber,
            vendorBankRoutingNumber: $routingNumber,
            vendorBankName: $bankName,
            vendorBankAccountName: $accountName,
            discountTaken: $discountTaken,
            withholdingTax: $withholdingTax,
            netAmount: $netAmount,
        );
    }

    /**
     * Create payment item for wire transfer
     */
    public static function forWire(
        string $paymentItemId,
        string $vendorId,
        string $vendorName,
        Money $amount,
        array $invoiceIds,
        string $paymentReference,
        string $bankAccountNumber,
        string $routingNumber,
        string $bankName,
        string $accountName,
        ?Money $discountTaken = null,
        ?Money $withholdingTax = null,
    ): self {
        $netAmount = $amount;
        if ($discountTaken !== null) {
            $netAmount = $netAmount->subtract($discountTaken);
        }
        if ($withholdingTax !== null) {
            $netAmount = $netAmount->subtract($withholdingTax);
        }

        return new self(
            paymentItemId: $paymentItemId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            amount: $amount,
            invoiceIds: $invoiceIds,
            paymentReference: $paymentReference,
            status: 'pending',
            vendorBankAccountNumber: $bankAccountNumber,
            vendorBankRoutingNumber: $routingNumber,
            vendorBankName: $bankName,
            vendorBankAccountName: $accountName,
            discountTaken: $discountTaken,
            withholdingTax: $withholdingTax,
            netAmount: $netAmount,
        );
    }

    /**
     * Create payment item for check
     */
    public static function forCheck(
        string $paymentItemId,
        string $vendorId,
        string $vendorName,
        Money $amount,
        array $invoiceIds,
        string $paymentReference,
        ?Money $discountTaken = null,
        ?Money $withholdingTax = null,
    ): self {
        $netAmount = $amount;
        if ($discountTaken !== null) {
            $netAmount = $netAmount->subtract($discountTaken);
        }
        if ($withholdingTax !== null) {
            $netAmount = $netAmount->subtract($withholdingTax);
        }

        return new self(
            paymentItemId: $paymentItemId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            amount: $amount,
            invoiceIds: $invoiceIds,
            paymentReference: $paymentReference,
            status: 'pending',
            discountTaken: $discountTaken,
            withholdingTax: $withholdingTax,
            netAmount: $netAmount,
        );
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment was processed
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment was reversed
     */
    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    /**
     * Check if discount was taken
     */
    public function hasDiscount(): bool
    {
        return $this->discountTaken !== null && !$this->discountTaken->isZero();
    }

    /**
     * Check if withholding tax applied
     */
    public function hasWithholdingTax(): bool
    {
        return $this->withholdingTax !== null && !$this->withholdingTax->isZero();
    }

    /**
     * Mark as processed with ACH trace number
     */
    public function withAchProcessed(string $achTraceNumber): self
    {
        return new self(
            paymentItemId: $this->paymentItemId,
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            amount: $this->amount,
            invoiceIds: $this->invoiceIds,
            paymentReference: $this->paymentReference,
            status: 'processed',
            vendorBankAccountNumber: $this->vendorBankAccountNumber,
            vendorBankRoutingNumber: $this->vendorBankRoutingNumber,
            vendorBankName: $this->vendorBankName,
            vendorBankAccountName: $this->vendorBankAccountName,
            discountTaken: $this->discountTaken,
            withholdingTax: $this->withholdingTax,
            netAmount: $this->netAmount,
            checkNumber: $this->checkNumber,
            wireReference: $this->wireReference,
            achTraceNumber: $achTraceNumber,
            failureReason: null,
            processedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
        );
    }

    /**
     * Mark as processed with wire reference
     */
    public function withWireProcessed(string $wireReference): self
    {
        return new self(
            paymentItemId: $this->paymentItemId,
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            amount: $this->amount,
            invoiceIds: $this->invoiceIds,
            paymentReference: $this->paymentReference,
            status: 'processed',
            vendorBankAccountNumber: $this->vendorBankAccountNumber,
            vendorBankRoutingNumber: $this->vendorBankRoutingNumber,
            vendorBankName: $this->vendorBankName,
            vendorBankAccountName: $this->vendorBankAccountName,
            discountTaken: $this->discountTaken,
            withholdingTax: $this->withholdingTax,
            netAmount: $this->netAmount,
            checkNumber: $this->checkNumber,
            wireReference: $wireReference,
            achTraceNumber: $this->achTraceNumber,
            failureReason: null,
            processedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
        );
    }

    /**
     * Mark as processed with check number
     */
    public function withCheckProcessed(string $checkNumber): self
    {
        return new self(
            paymentItemId: $this->paymentItemId,
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            amount: $this->amount,
            invoiceIds: $this->invoiceIds,
            paymentReference: $this->paymentReference,
            status: 'processed',
            vendorBankAccountNumber: $this->vendorBankAccountNumber,
            vendorBankRoutingNumber: $this->vendorBankRoutingNumber,
            vendorBankName: $this->vendorBankName,
            vendorBankAccountName: $this->vendorBankAccountName,
            discountTaken: $this->discountTaken,
            withholdingTax: $this->withholdingTax,
            netAmount: $this->netAmount,
            checkNumber: $checkNumber,
            wireReference: $this->wireReference,
            achTraceNumber: $this->achTraceNumber,
            failureReason: null,
            processedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
        );
    }

    /**
     * Mark as failed
     */
    public function withFailure(string $reason): self
    {
        return new self(
            paymentItemId: $this->paymentItemId,
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            amount: $this->amount,
            invoiceIds: $this->invoiceIds,
            paymentReference: $this->paymentReference,
            status: 'failed',
            vendorBankAccountNumber: $this->vendorBankAccountNumber,
            vendorBankRoutingNumber: $this->vendorBankRoutingNumber,
            vendorBankName: $this->vendorBankName,
            vendorBankAccountName: $this->vendorBankAccountName,
            discountTaken: $this->discountTaken,
            withholdingTax: $this->withholdingTax,
            netAmount: $this->netAmount,
            checkNumber: $this->checkNumber,
            wireReference: $this->wireReference,
            achTraceNumber: $this->achTraceNumber,
            failureReason: $reason,
            processedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'payment_item_id' => $this->paymentItemId,
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'amount' => $this->amount->toArray(),
            'invoice_ids' => $this->invoiceIds,
            'invoice_count' => count($this->invoiceIds),
            'payment_reference' => $this->paymentReference,
            'status' => $this->status,
            'discount_taken' => $this->discountTaken?->toArray(),
            'withholding_tax' => $this->withholdingTax?->toArray(),
            'net_amount' => $this->netAmount?->toArray(),
            'check_number' => $this->checkNumber,
            'check_date' => $this->checkDate?->format('Y-m-d'),
            'check_type' => $this->checkType,
            'wire_reference' => $this->wireReference,
            'ach_trace_number' => $this->achTraceNumber,
            'beneficiary_bic' => $this->beneficiaryBic,
            'beneficiary_iban' => $this->beneficiaryIban,
            'beneficiary_name' => $this->beneficiaryName,
            'beneficiary_address' => $this->beneficiaryAddress,
            'beneficiary_country' => $this->beneficiaryCountry,
            'intermediary_bic' => $this->intermediaryBic,
            'charge_code' => $this->chargeCode,
            'failure_reason' => $this->failureReason,
            'processed_at' => $this->processedAt?->format('c'),
        ];
    }

    /**
     * Create payment item for international wire transfer (SWIFT)
     */
    public static function forInternationalWire(
        string $paymentItemId,
        string $vendorId,
        string $vendorName,
        Money $amount,
        array $invoiceIds,
        string $paymentReference,
        string $beneficiaryBic,
        string $beneficiaryIban,
        string $beneficiaryName,
        ?string $beneficiaryAddress = null,
        ?string $beneficiaryCountry = null,
        ?string $intermediaryBic = null,
        string $chargeCode = 'SHA',
        ?Money $discountTaken = null,
        ?Money $withholdingTax = null,
    ): self {
        $netAmount = $amount;
        if ($discountTaken !== null) {
            $netAmount = $netAmount->subtract($discountTaken);
        }
        if ($withholdingTax !== null) {
            $netAmount = $netAmount->subtract($withholdingTax);
        }

        return new self(
            paymentItemId: $paymentItemId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            amount: $amount,
            invoiceIds: $invoiceIds,
            paymentReference: $paymentReference,
            status: 'pending',
            discountTaken: $discountTaken,
            withholdingTax: $withholdingTax,
            netAmount: $netAmount,
            beneficiaryBic: $beneficiaryBic,
            beneficiaryIban: $beneficiaryIban,
            beneficiaryName: $beneficiaryName,
            beneficiaryAddress: $beneficiaryAddress,
            beneficiaryCountry: $beneficiaryCountry,
            intermediaryBic: $intermediaryBic,
            chargeCode: $chargeCode,
        );
    }
}
