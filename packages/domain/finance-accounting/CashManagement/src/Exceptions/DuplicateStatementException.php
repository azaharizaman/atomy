<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Exceptions;

use RuntimeException;

/**
 * Duplicate Statement Exception
 *
 * Thrown when attempting to import a statement that already exists.
 */
class DuplicateStatementException extends RuntimeException
{
    public function __construct(
        string $message = 'Bank statement already imported',
        private readonly ?string $existingStatementId = null,
        private readonly ?string $statementHash = null
    ) {
        parent::__construct($message);
    }

    public function getExistingStatementId(): ?string
    {
        return $this->existingStatementId;
    }

    public function getStatementHash(): ?string
    {
        return $this->statementHash;
    }

    public static function withHash(string $hash, string $existingId): self
    {
        return new self(
            message: sprintf('Statement with hash "%s" already imported (ID: %s)', substr($hash, 0, 16), $existingId),
            existingStatementId: $existingId,
            statementHash: $hash
        );
    }
}
