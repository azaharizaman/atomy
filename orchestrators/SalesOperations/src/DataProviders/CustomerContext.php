<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DataProviders;

final readonly class CustomerContext
{
    public function __construct(
        public string $customerId,
        public string $tenantId,
        public string $name,
        public ?string $code,
        public string $currencyCode,
        public string $paymentTerms,
        public ?string $pricingGroupId,
        public ?string $salespersonId,
        public bool $isActive,
        public float $creditLimit,
        public float $availableCredit,
        public array $credit,
        public array $orders,
    ) {}

    public function hasCreditLimit(): bool
    {
        return $this->creditLimit > 0;
    }

    public function isOnCreditHold(): bool
    {
        return $this->credit['on_hold'] ?? false;
    }

    public function canPlaceOrder(float $amount): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->isOnCreditHold()) {
            return false;
        }

        if ($this->hasCreditLimit() && $amount > $this->availableCredit) {
            return false;
        }

        return true;
    }
}
