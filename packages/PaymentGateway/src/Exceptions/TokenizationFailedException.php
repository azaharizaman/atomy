<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

/**
 * Exception thrown when tokenization fails.
 */
class TokenizationFailedException extends GatewayException
{
    public function __construct(
        string $message,
        public readonly ?string $reason = null,
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
     * Create for invalid card data.
     */
    public static function invalidCardData(string $reason): self
    {
        return new self(
            message: 'Invalid card data provided',
            reason: $reason,
            gatewayErrorCode: 'invalid_card_data',
        );
    }

    /**
     * Create for card not supported.
     */
    public static function cardNotSupported(string $cardType): self
    {
        return new self(
            message: sprintf('Card type %s is not supported', $cardType),
            reason: 'unsupported_card_type',
            gatewayErrorCode: 'card_not_supported',
        );
    }

    /**
     * Create for tokenization service unavailable.
     */
    public static function serviceUnavailable(): self
    {
        return new self(
            message: 'Tokenization service is currently unavailable',
            reason: 'service_unavailable',
            gatewayErrorCode: 'service_unavailable',
        );
    }
}
