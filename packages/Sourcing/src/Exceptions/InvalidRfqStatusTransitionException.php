<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Exceptions;

final class InvalidRfqStatusTransitionException extends \RuntimeException
{
    public static function fromStatuses(string $fromStatus, string $toStatus): self
    {
        return new self(sprintf(
            'Invalid RFQ status transition from "%s" to "%s".',
            trim($fromStatus),
            trim($toStatus),
        ));
    }
}
