<?php

declare(strict_types=1);

namespace Nexus\Outbox\Exceptions;

use Nexus\Outbox\Enums\OutboxRecordStatus;

final class OutboxInvalidTransitionException extends OutboxException
{
    public static function forStatus(OutboxRecordStatus $current, string $operation): self
    {
        return new self(\sprintf(
            'Invalid outbox transition for operation %s from status %s.',
            $operation,
            $current->name,
        ));
    }
}
