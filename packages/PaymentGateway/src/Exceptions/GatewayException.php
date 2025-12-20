<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

/**
 * Base exception for all PaymentGateway errors.
 */
class GatewayException extends \Exception
{
    public function __construct(
        string $message,
        public readonly ?string $gatewayErrorCode = null,
        public readonly ?string $gatewayMessage = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create from a gateway response.
     */
    public static function fromGatewayResponse(
        string $message,
        string $errorCode,
        ?string $gatewayMessage = null,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: $message,
            gatewayErrorCode: $errorCode,
            gatewayMessage: $gatewayMessage,
            previous: $previous,
        );
    }
}
