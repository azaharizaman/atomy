<?php

declare(strict_types=1);

namespace Nexus\Outbox\Exceptions;

final class OutboxClaimExpiredException extends OutboxException
{
    public static function create(): self
    {
        return new self('Outbox claim lease has expired.');
    }
}
