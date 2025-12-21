<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\ValueObjects\GatewayError;

/**
 * Exception thrown when payment authorization fails.
 */
class AuthorizationFailedException extends GatewayException
{
    public function __construct(
        string $message,
        public readonly ?GatewayError $error = null,
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
     * Create for card declined.
     */
    public static function cardDeclined(
        Money $amount,
        ?string $declineCode = null,
        ?string $declineMessage = null,
    ): self {
        return new self(
            message: 'Card was declined',
            error: GatewayError::cardDeclined($declineCode, $declineMessage),
            attemptedAmount: $amount,
            gatewayErrorCode: $declineCode,
            gatewayMessage: $declineMessage,
        );
    }

    /**
     * Create for insufficient funds.
     */
    public static function insufficientFunds(Money $amount): self
    {
        return new self(
            message: 'Insufficient funds',
            error: GatewayError::insufficientFunds(),
            attemptedAmount: $amount,
            gatewayErrorCode: 'insufficient_funds',
        );
    }

    /**
     * Create for expired card.
     */
    public static function expiredCard(Money $amount): self
    {
        return new self(
            message: 'Card has expired',
            error: GatewayError::expiredCard(),
            attemptedAmount: $amount,
            gatewayErrorCode: 'expired_card',
        );
    }

    /**
     * Create from gateway response.
     */
    public static function fromGatewayResponse(
        string $message,
        string $errorCode,
        ?string $gatewayMessage = null,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: $message,
            error: GatewayError::fromArray(['code' => $errorCode, 'message' => $gatewayMessage]),
            attemptedAmount: null,
            gatewayErrorCode: $errorCode,
            gatewayMessage: $gatewayMessage,
            previous: $previous,
        );
    }
}
