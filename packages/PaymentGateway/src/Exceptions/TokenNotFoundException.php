<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

/**
 * Exception thrown when a token is not found.
 */
class TokenNotFoundException extends GatewayException
{
    public function __construct(
        public readonly string $tokenId,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: sprintf('Token not found: %s', $tokenId),
            gatewayErrorCode: 'token_not_found',
            gatewayMessage: null,
            previous: $previous,
        );
    }
}
