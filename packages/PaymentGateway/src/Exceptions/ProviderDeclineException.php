<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

class ProviderDeclineException extends GatewayException
{
    public function __construct(
        string $message,
        public readonly ?string $declineCode = null,
        ?string $gatewayErrorCode = null,
        ?string $gatewayMessage = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $gatewayErrorCode, $gatewayMessage, $previous);
    }
}
