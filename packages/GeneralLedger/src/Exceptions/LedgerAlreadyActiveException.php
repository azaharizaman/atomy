<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Ledger Already Active Exception
 * 
 * Thrown when attempting to reactivate a ledger that is already active.
 */
final class LedgerAlreadyActiveException extends GeneralLedgerException
{
    public function __construct(
        private readonly string $ledgerId,
        string $message = '',
        int $code = 409
    ) {
        $message = $message ?: "Ledger is already active: {$ledgerId}";
        parent::__construct($message, $code);
    }

    public function getLedgerId(): string
    {
        return $this->ledgerId;
    }
}
