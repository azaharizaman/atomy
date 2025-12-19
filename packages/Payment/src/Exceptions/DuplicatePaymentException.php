<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when a duplicate payment is detected via idempotency check.
 */
final class DuplicatePaymentException extends PaymentException
{
    public function __construct(
        private readonly string $originalPaymentId,
        string $idempotencyKey,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Duplicate payment detected. Original payment ID: %s (idempotency key: %s)',
                $this->originalPaymentId,
                $idempotencyKey,
            ),
            409,
            $previous,
            [
                'original_payment_id' => $this->originalPaymentId,
                'idempotency_key' => $idempotencyKey,
            ],
        );
    }

    /**
     * Get the original payment ID.
     */
    public function getOriginalPaymentId(): string
    {
        return $this->originalPaymentId;
    }
}
