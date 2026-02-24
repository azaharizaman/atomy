<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Ledger Not Found Exception
 * 
 * Thrown when a requested ledger does not exist.
 */
final class LedgerNotFoundException extends GeneralLedgerException
{
    public function __construct(
        private readonly string $ledgerId,
        string $message = '',
        int $code = 404
    ) {
        $message = $message ?: "Ledger not found: {$ledgerId}";
        parent::__construct($message, $code);
    }

    public function getLedgerId(): string
    {
        return $this->ledgerId;
    }
}
