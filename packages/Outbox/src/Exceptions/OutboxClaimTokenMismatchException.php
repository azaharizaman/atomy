<?php

declare(strict_types=1);

namespace Nexus\Outbox\Exceptions;

final class OutboxClaimTokenMismatchException extends OutboxException
{
    public static function create(): self
    {
        return new self('Claim token does not match the outbox message.');
    }
}
