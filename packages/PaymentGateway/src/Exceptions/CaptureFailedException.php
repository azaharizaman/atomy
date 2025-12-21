<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

use Nexus\Common\ValueObjects\Money;

/**
 * Exception thrown when payment capture fails.
 */
class CaptureFailedException extends GatewayException
{
    public function __construct(
        string $message,
        public readonly ?string $authorizationId = null,
        public readonly ?Money $attemptedAmount = null,
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
     * Create for authorization expired.
     */
    public static function authorizationExpired(string $authorizationId): self
    {
        return new self(
            message: 'Authorization has expired and cannot be captured',
            authorizationId: $authorizationId,
            gatewayErrorCode: 'authorization_expired',
        );
    }

    /**
     * Create for already captured.
     */
    public static function alreadyCaptured(string $authorizationId): self
    {
        return new self(
            message: 'Authorization has already been fully captured',
            authorizationId: $authorizationId,
            gatewayErrorCode: 'already_captured',
        );
    }

    /**
     * Create for amount exceeds authorization.
     */
    public static function amountExceedsAuthorization(
        string $authorizationId,
        Money $attemptedAmount,
        Money $authorizedAmount,
    ): self {
        return new self(
            message: sprintf(
                'Capture amount %s exceeds authorized amount %s',
                $attemptedAmount->format(),
                $authorizedAmount->format(),
            ),
            authorizationId: $authorizationId,
            attemptedAmount: $attemptedAmount,
            gatewayErrorCode: 'amount_exceeds_authorization',
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
            message: $gatewayMessage ?? 'Capture failed',
            authorizationId: $authorizationId,
            gatewayErrorCode: $errorCode,
            gatewayMessage: $gatewayMessage,
            previous: $previous,
        );
    }
}
