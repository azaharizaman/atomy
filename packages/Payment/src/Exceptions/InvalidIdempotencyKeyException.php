<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when an idempotency key is invalid.
 */
final class InvalidIdempotencyKeyException extends PaymentException
{
    public function __construct(
        string $message,
        ?string $key = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            400,
            $previous,
            array_filter(['idempotency_key' => $key]),
        );
    }

    /**
     * Create for an expired key.
     */
    public static function expired(string $key): self
    {
        return new self(
            sprintf('Idempotency key "%s" has expired', $key),
            $key,
        );
    }

    /**
     * Create for a duplicate key with different parameters.
     */
    public static function mismatch(string $key): self
    {
        return new self(
            sprintf('Idempotency key "%s" was already used with different parameters', $key),
            $key,
        );
    }
}
