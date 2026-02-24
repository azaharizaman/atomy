<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Period Closed Exception
 * 
 * Thrown when attempting to post a transaction to a closed fiscal period.
 */
final class PeriodClosedException extends GeneralLedgerException
{
    public function __construct(
        private readonly string $periodId,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: "Cannot post to closed period: {$periodId}";
        parent::__construct($message, $code);
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }
}
