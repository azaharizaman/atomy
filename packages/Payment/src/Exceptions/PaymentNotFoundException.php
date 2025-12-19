<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when a payment transaction cannot be found.
 */
final class PaymentNotFoundException extends PaymentException
{
    private function __construct(
        string $message,
        array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            404,
            $previous,
            $context,
        );
    }

    /**
     * Create for a payment ID lookup.
     */
    public static function forId(string $paymentId): self
    {
        return new self(
            sprintf('Payment transaction with ID "%s" not found', $paymentId),
            ['payment_id' => $paymentId]
        );
    }

    /**
     * Create for a payment reference lookup.
     */
    public static function forReference(string $reference): self
    {
        return new self(
            sprintf('Payment transaction with reference "%s" not found', $reference),
            ['payment_reference' => $reference]
        );
    }
}
