<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankTransactionInterface;

interface BankTransactionQueryInterface
{
    public function findById(string $id): ?BankTransactionInterface;
    
    /**
     * @return BankTransactionInterface[]
     */
    public function findByConnectionId(string $connectionId, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array;

    /**
     * @return BankTransactionInterface[]
     */
    public function findByConnectionAndDateRange(string $connectionId, \DateTimeImmutable $start, \DateTimeImmutable $end): array;
    
    /**
     * @return BankTransactionInterface[]
     */
    public function findUnreconciled(string $connectionId): array;
}
