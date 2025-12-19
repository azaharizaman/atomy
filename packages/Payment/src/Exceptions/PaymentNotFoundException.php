<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when a payment transaction cannot be found.
 */
final class PaymentNotFoundException extends PaymentException
{
    public function __construct(
        string $paymentId,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Payment transaction with ID "%s" not found', $paymentId),
            404,
            $previous,
            ['payment_id' => $paymentId],
        );
    }

    /**
     * Create for a payment reference lookup.
     */
    public static function forReference(string $reference): self
    {
        $exception = new self($reference);

        return new self(
            sprintf('Payment transaction with reference "%s" not found', $reference),
            404,
            null,
        );
    }
}
