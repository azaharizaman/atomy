<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\ValueObjects;

final readonly class Beneficiary
{
    public function __construct(
        public string $name,
        public string $iban, // or accountNumber + routingNumber
        public ?string $bic = null,
        public ?string $address = null
    ) {}
}
