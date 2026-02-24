<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Invalid Transaction Exception
 * 
 * Thrown when a transaction entity is in an invalid state.
 */
final class InvalidTransactionException extends GeneralLedgerException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
