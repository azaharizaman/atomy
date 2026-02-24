<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Account Not Found Exception
 * 
 * Thrown when a requested ledger account does not exist.
 */
final class AccountNotFoundException extends GeneralLedgerException
{
    public function __construct(
        private readonly string $accountId,
        string $message = '',
        int $code = 404
    ) {
        $message = $message ?: "Account not found: {$accountId}";
        parent::__construct($message, $code);
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }
}
