<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankTransactionInterface;

interface ReconciliationResultInterface
{
    /**
     * @return BankTransactionInterface[]
     */
    public function getMatchedTransactions(): array;

    /**
     * @return BankTransactionInterface[]
     */
    public function getUnmatchedTransactions(): array;
}
