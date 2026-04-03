<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Exceptions;

final class RfqLifecyclePreconditionException extends \RuntimeException
{
    public function isNotFound(): bool
    {
        return $this instanceof RfqNotFoundException;
    }

    public static function forReason(string $reason): self
    {
        return new self($reason);
    }

    public static function forRfq(string $rfqId, string $reason): self
    {
        return new self(sprintf('RFQ "%s": %s', trim($rfqId), $reason));
    }
}
