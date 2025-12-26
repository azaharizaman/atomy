<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankConnectionInterface;

interface BankConnectionPersistInterface
{
    public function save(BankConnectionInterface $connection): BankConnectionInterface;
    public function delete(string $id): void;
}
