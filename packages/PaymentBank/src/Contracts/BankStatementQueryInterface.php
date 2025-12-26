<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankStatementInterface;

interface BankStatementQueryInterface
{
    public function findById(string $id): ?BankStatementInterface;
    
    /**
     * @return BankStatementInterface[]
     */
    public function findByConnectionId(string $connectionId): array;
}
