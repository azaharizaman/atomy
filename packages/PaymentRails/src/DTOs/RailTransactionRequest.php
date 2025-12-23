<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\ValueObjects\BankAccount;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;

/**
 * Data transfer object for validating a payment rail transaction.
 */
final readonly class RailTransactionRequest
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        private string $beneficiaryName,
        private Money $amount,
        private ?BankAccount $beneficiaryAccount = null,
        // Optional standalone routing number.
        // Prefer providing routing via $beneficiaryAccount->routingNumber when available.
        private ?RoutingNumber $routingNumber = null,
        private ?string $beneficiaryCountry = null,
        private ?string $beneficiaryAddress = null,
        private bool $isInternational = false,
        private ?string $purposeOfPayment = null,
        private ?string $memo = null,
        private ?array $metadata = null,
    ) {
    }

    /**
     * Get beneficiary name.
     */
    public function getBeneficiaryName(): string
    {
        return $this->beneficiaryName;
    }

    /**
     * Get transaction amount.
     */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /**
     * Get beneficiary account details.
     */
    public function getBeneficiaryAccount(): ?BankAccount
    {
        return $this->beneficiaryAccount;
    }

    /**
     * Get routing number.
     */
    public function getRoutingNumber(): ?RoutingNumber
    {
        return $this->routingNumber;
    }

    /**
     * Get beneficiary country.
     */
    public function getBeneficiaryCountry(): ?string
    {
        return $this->beneficiaryCountry;
    }

    /**
     * Get beneficiary address.
     */
    public function getBeneficiaryAddress(): ?string
    {
        return $this->beneficiaryAddress;
    }

    /**
     * Check whether the transaction is international.
     */
    public function isInternational(): bool
    {
        return $this->isInternational;
    }

    /**
     * Get purpose of payment.
     */
    public function getPurposeOfPayment(): ?string
    {
        return $this->purposeOfPayment;
    }

    /**
     * Get memo.
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * Get metadata.
     *
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}
