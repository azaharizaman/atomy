<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Enums\ProviderType;

interface ProviderRegistryInterface
{
    public function getProvider(ProviderType $type): BankProviderInterface;

    public function get(string $name): BankProviderInterface;
}
