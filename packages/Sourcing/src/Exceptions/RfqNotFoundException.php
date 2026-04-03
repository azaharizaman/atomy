<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Exceptions;

final class RfqNotFoundException extends \RuntimeException
{
    public static function forId(string $rfqId, string $tenantId): self
    {
        return new self(sprintf('RFQ "%s" could not be found for tenant "%s".', trim($rfqId), trim($tenantId)));
    }
}
