<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankConnectionInterface;

interface BankConnectionQueryInterface
{
    public function findById(string $id): ?BankConnectionInterface;
    
    /**
     * @return array<BankConnectionInterface>
     */
    public function findByTenantId(string $tenantId): array;
    
    public function findByProviderConnectionId(string $providerConnectionId): ?BankConnectionInterface;
}
