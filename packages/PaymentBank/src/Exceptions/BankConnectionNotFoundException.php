<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Exceptions;

class BankConnectionNotFoundException extends PaymentBankException
{
    public function __construct(string $id)
    {
        parent::__construct("Bank connection with ID {$id} not found.");
    }
}
