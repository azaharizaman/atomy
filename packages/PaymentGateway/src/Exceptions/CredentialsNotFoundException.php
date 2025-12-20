<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

use Nexus\PaymentGateway\Enums\GatewayProvider;

/**
 * Exception thrown when credentials are not found.
 */
class CredentialsNotFoundException extends GatewayException
{
    public function __construct(
        public readonly string $tenantId,
        public readonly GatewayProvider $provider,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Credentials not found for tenant %s and provider %s',
                $tenantId,
                $provider->value,
            ),
            gatewayErrorCode: 'credentials_not_found',
            gatewayMessage: null,
            previous: $previous,
        );
    }
}
