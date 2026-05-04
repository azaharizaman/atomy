<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Exceptions;

use RuntimeException;

final class AiTransportFailedException extends RuntimeException
{
    public function __construct(
        string $message = '',
        private readonly string $reasonCode = 'provider_unavailable',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function reasonCode(): string
    {
        return $this->reasonCode;
    }
}
