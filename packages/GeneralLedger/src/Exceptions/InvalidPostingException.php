<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Invalid Posting Exception
 * 
 * Thrown when a transaction cannot be posted due to validation errors.
 */
final class InvalidPostingException extends GeneralLedgerException
{
    public function __construct(
        private readonly string $reason,
        private readonly ?string $accountId = null,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: "Invalid posting: {$reason}";
        parent::__construct($message, $code);
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getAccountId(): ?string
    {
        return $this->accountId;
    }
}
