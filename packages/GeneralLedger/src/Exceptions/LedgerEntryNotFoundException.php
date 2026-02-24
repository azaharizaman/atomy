<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Ledger Entry Not Found Exception
 * 
 * Thrown when a specific transaction/entry is not found.
 */
final class LedgerEntryNotFoundException extends GeneralLedgerException
{
    public function __construct(string $entryId)
    {
        parent::__construct(
            sprintf('Ledger entry %s not found', $entryId)
        );
    }
}
