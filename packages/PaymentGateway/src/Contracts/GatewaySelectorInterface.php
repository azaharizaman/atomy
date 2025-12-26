<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;

interface GatewaySelectorInterface
{
    /**
     * Select the best gateway provider for the given request.
     *
     * @param AuthorizeRequest $request The authorization request context
     * @return GatewayProvider The selected provider
     */
    public function select(AuthorizeRequest $request): GatewayProvider;
}
