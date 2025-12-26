<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Exceptions;

final class BankStatementNotFoundException extends PaymentBankException
{
    public function __construct(string $statementId)
    {
        parent::__construct(
            "Bank statement not found: {$statementId}",
            404
        );
    }
}
