<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when recipient information is invalid.
 */
final class InvalidRecipientInfoException extends PaymentException
{
    public function __construct(
        string $message,
        ?string $recipientId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            400,
            $previous,
            array_filter(['recipient_id' => $recipientId]),
        );
    }
}
