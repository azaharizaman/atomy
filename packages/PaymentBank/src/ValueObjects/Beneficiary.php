<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\ValueObjects;

final readonly class Beneficiary
{
    public function __construct(
        public string $name,
        public string $iban, // International Bank Account Number or account number + routing number
        public ?string $bic = null,
        public ?string $address = null
    ) {}
}
