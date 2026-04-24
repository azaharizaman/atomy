<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Exceptions;

use RuntimeException;

final class AiStatusContractException extends RuntimeException
{
    public static function invalidValue(string $subject): self
    {
        return new self(sprintf('Invalid %s configuration.', $subject));
    }

    public static function unsupportedMode(string $subject): self
    {
        return new self(sprintf('Unsupported %s configuration.', $subject));
    }
}
