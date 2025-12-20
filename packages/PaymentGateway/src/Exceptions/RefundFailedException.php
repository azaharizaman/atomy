<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

use Nexus\Common\ValueObjects\Money;

/**
 * Exception thrown when refund fails.
 */
class RefundFailedException extends GatewayException
{
    public function __construct(
        string $message,
        public readonly ?string $transactionId = null,
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
     * Create for already refunded.
     */
    public static function alreadyRefunded(string $transactionId): self
    {
        return new self(
            message: 'Transaction has already been fully refunded',
            transactionId: $transactionId,
            gatewayErrorCode: 'already_refunded',
        );
    }

    /**
     * Create for amount exceeds capture.
     */
    public static function amountExceedsCapture(
        string $transactionId,
        Money $attemptedAmount,
        Money $capturedAmount,
    ): self {
        return new self(
            message: sprintf(
                'Refund amount %s exceeds captured amount %s',
                $attemptedAmount->format(),
                $capturedAmount->format(),
            ),
            transactionId: $transactionId,
            attemptedAmount: $attemptedAmount,
            gatewayErrorCode: 'amount_exceeds_capture',
        );
    }

    /**
     * Create for refund window expired.
     */
    public static function refundWindowExpired(string $transactionId): self
    {
        return new self(
            message: 'Refund window has expired for this transaction',
            transactionId: $transactionId,
            gatewayErrorCode: 'refund_window_expired',
        );
    }

    /**
     * Create from gateway response.
     */
    public static function fromGatewayResponse(
        string $errorCode,
        ?string $gatewayMessage = null,
        ?string $transactionId = null,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: $gatewayMessage ?? 'Refund failed',
            transactionId: $transactionId,
            gatewayErrorCode: $errorCode,
            gatewayMessage: $gatewayMessage,
            previous: $previous,
        );
    }
}
