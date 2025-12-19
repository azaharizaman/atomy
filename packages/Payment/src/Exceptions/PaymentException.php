<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Base exception for all payment-related errors.
 */
class PaymentException extends \Exception
{
    /**
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     * @param array<string, mixed> $context Additional error context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the error context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get a specific context value.
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }
}
