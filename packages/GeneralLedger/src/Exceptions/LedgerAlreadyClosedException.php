<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Ledger Already Closed Exception
 * 
 * Thrown when attempting to close a ledger that is already closed.
 */
final class LedgerAlreadyClosedException extends GeneralLedgerException
{
    public function __construct(
        private readonly string $ledgerId,
        string $message = '',
        int $code = 409
    ) {
        $message = $message ?: "Ledger is already closed: {$ledgerId}";
        parent::__construct($message, $code);
    }

    public function getLedgerId(): string
    {
        return $this->ledgerId;
    }
}
