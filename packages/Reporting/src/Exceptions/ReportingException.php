<?php

declare(strict_types=1);

namespace Nexus\Reporting\Exceptions;

/**
 * Base exception for all Reporting package errors.
 */
class ReportingException extends \RuntimeException
{
    /**
     * Create a new reporting exception.
     *
     * @param string $message Error message
     * @param int $code Error code (default: 0)
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception with context data.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @param \Throwable|null $previous
     */
    public static function withContext(
        string $message,
        array $context = [],
        ?\Throwable $previous = null
    ): static {
        $contextStr = empty($context) ? '' : ' Context: ' . json_encode($context);
        return new static($message . $contextStr, 0, $previous);
    }
}
