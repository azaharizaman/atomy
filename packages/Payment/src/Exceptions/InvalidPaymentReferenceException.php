<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when a payment reference is invalid.
 */
final class InvalidPaymentReferenceException extends PaymentException
{
    public function __construct(
        string $message,
        ?string $reference = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            400,
            $previous,
            array_filter(['reference' => $reference]),
        );
    }
}
