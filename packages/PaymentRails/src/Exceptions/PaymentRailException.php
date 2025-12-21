<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

/**
 * Base exception for all payment rail errors.
 */
class PaymentRailException extends \Exception
{
    /**
     * @param string $message
     * @param string|null $railType
     * @param array<string, mixed> $context
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        protected readonly ?string $railType = null,
        protected readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the rail type associated with this exception.
     */
    public function getRailType(): ?string
    {
        return $this->railType;
    }

    /**
     * Get additional context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create an exception for rail unavailable.
     */
    public static function railUnavailable(string $railType, string $reason): self
    {
        return new self(
            message: "Payment rail '{$railType}' is currently unavailable: {$reason}",
            railType: $railType,
            context: ['reason' => $reason],
        );
    }

    /**
     * Create an exception for unsupported operation.
     */
    public static function unsupportedOperation(string $railType, string $operation): self
    {
        return new self(
            message: "Operation '{$operation}' is not supported by rail '{$railType}'",
            railType: $railType,
            context: ['operation' => $operation],
        );
    }

    /**
     * Create an exception for configuration error.
     */
    public static function configurationError(string $railType, string $message): self
    {
        return new self(
            message: "Configuration error for rail '{$railType}': {$message}",
            railType: $railType,
        );
    }
}
