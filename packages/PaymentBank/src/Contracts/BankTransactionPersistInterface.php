<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankTransactionInterface;

interface BankTransactionPersistInterface
{
    public function save(BankTransactionInterface $transaction): BankTransactionInterface;
    
    /**
     * @param BankTransactionInterface[] $transactions
     */
    public function saveMany(array $transactions): void;
    
    public function delete(string $id): void;
}
