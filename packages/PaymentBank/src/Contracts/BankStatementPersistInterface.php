<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankStatementInterface;

interface BankStatementPersistInterface
{
    public function save(BankStatementInterface $statement): BankStatementInterface;
    
    public function delete(string $id): void;
}
