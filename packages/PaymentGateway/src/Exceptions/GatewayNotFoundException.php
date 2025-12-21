<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

use Nexus\PaymentGateway\Enums\GatewayProvider;

/**
 * Exception thrown when gateway is not found.
 */
class GatewayNotFoundException extends GatewayException
{
    public function __construct(
        public readonly GatewayProvider $provider,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: sprintf('Gateway not found for provider: %s', $provider->value),
            gatewayErrorCode: 'gateway_not_found',
            gatewayMessage: null,
            previous: $previous,
        );
    }
}
