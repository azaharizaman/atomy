<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentBank\ValueObjects\Beneficiary;

final readonly class PaymentInitiationRequest
{
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
