<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

class RateLimitException extends GatewayException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfter = null,
        ?string $gatewayErrorCode = null,
        ?string $gatewayMessage = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $gatewayErrorCode, $gatewayMessage, $previous);
    }
}
