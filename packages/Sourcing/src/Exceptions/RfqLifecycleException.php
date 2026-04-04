<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Exceptions;

final class RfqLifecycleException extends \RuntimeException
{
    public static function validationFailed(string $message): self
    {
        return new self($message);
    }
}
