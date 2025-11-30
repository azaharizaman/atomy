<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Exceptions;

use RuntimeException;

/**
 * Base exception for all Monitoring package errors.
 * 
 * Provides context serialization for structured logging and debugging.
 */
class MonitoringException extends RuntimeException
{
    /**
     * @param array<string, mixed> $context Additional context data
     */
    public function __construct(
        string $message,
        private readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get the exception context for logging.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Get HTTP status code for API responses.
     */
    public function getHttpStatus(): int
    {
        return 500; // Internal Server Error (default)
    }
}
