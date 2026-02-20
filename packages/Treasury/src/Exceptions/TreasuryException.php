<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

use RuntimeException;

class TreasuryException extends RuntimeException
{
    public static function forReason(string $reason, int $code = 0): self
    {
        return new self($reason, $code);
    }
}
