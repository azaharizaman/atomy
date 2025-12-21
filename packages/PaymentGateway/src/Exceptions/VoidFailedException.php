<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

/**
 * Exception thrown when void/cancel operation fails.
 */
class VoidFailedException extends GatewayException
{
    public function __construct(
        string $message,
        public readonly ?string $authorizationId = null,
        ?string $gatewayErrorCode = null,
        ?string $gatewayMessage = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            gatewayErrorCode: $gatewayErrorCode,
            gatewayMessage: $gatewayMessage,
            previous: $previous,
        );
    }

    /**
     * Create for already captured.
     */
    public static function alreadyCaptured(string $authorizationId): self
    {
        return new self(
            message: 'Cannot void authorization that has already been captured',
            authorizationId: $authorizationId,
            gatewayErrorCode: 'already_captured',
        );
    }

    /**
     * Create for already voided.
     */
    public static function alreadyVoided(string $authorizationId): self
    {
        return new self(
            message: 'Authorization has already been voided',
            authorizationId: $authorizationId,
            gatewayErrorCode: 'already_voided',
        );
    }

    /**
     * Create for void not supported.
     */
    public static function voidNotSupported(string $authorizationId): self
    {
        return new self(
            message: 'Void operation is not supported for this transaction type',
            authorizationId: $authorizationId,
            gatewayErrorCode: 'void_not_supported',
        );
    }

    /**
     * Create from gateway response.
     */
    public static function fromGatewayResponse(
        string $errorCode,
        ?string $gatewayMessage = null,
        ?string $authorizationId = null,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: $gatewayMessage ?? 'Void failed',
            authorizationId: $authorizationId,
            gatewayErrorCode: $errorCode,
            gatewayMessage: $gatewayMessage,
            previous: $previous,
        );
    }
}
