<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

/**
 * Lightweight bank account representation used for rail validation.
 */
final class BankAccount
{
    public function __construct(
        public readonly string $accountNumber,
        public readonly ?RoutingNumber $routingNumber = null,
        public readonly ?SwiftCode $swiftCode = null,
        public readonly ?Iban $iban = null,
    ) {
    }
}
