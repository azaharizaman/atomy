<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentBank\ValueObjects\Beneficiary;

/**
 * Payment initiation request data transfer object.
 */
final readonly class PaymentInitiationRequest
{
    /**
     * @param string $sourceAccountId Source account for the payment
     * @param Beneficiary $beneficiary Payment recipient details
     * @param Money $amount Amount to transfer
     * @param string $reference Payment reference/description
     * @param array<string, mixed> $options Provider-specific payment options
     */
    public function __construct(
        private string $sourceAccountId,
        private Beneficiary $beneficiary,
        private Money $amount,
        private string $reference,
        private array $options = []
    ) {}

    public function getSourceAccountId(): string
    {
        return $this->sourceAccountId;
    }

    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
