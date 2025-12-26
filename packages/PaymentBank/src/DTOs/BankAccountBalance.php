<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\Common\ValueObjects\Money;

final readonly class BankAccountBalance
{
    public function __construct(
        public Money $available,
        public Money $current,
        public string $currencyCode,
        public ?Money $limit = null
    ) {}
}
