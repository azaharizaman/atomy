<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Ledger Archived Exception
 * 
 * Thrown when an operation is attempted on an archived ledger.
 */
final class LedgerArchivedException extends GeneralLedgerException
{
    public function __construct(string $ledgerId)
    {
        parent::__construct(
            sprintf('Ledger %s is archived and cannot be modified', $ledgerId)
        );
    }
}
