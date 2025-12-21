<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

/**
 * Exception thrown when gateway credentials are invalid.
 */
class InvalidCredentialsException extends GatewayException
{
    public function __construct(
        string $message = 'Invalid gateway credentials',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            gatewayErrorCode: 'invalid_credentials',
            gatewayMessage: $message,
            previous: $previous,
        );
    }
}
