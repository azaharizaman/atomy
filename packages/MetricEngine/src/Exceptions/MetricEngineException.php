<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

use RuntimeException;

abstract class MetricEngineException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
