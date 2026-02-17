<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\GatewaySelectorInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Exceptions\GatewayException;

final readonly class SmartGatewaySelector implements GatewaySelectorInterface
{
    public function __construct(
        private ?GatewayProvider $defaultProvider = null
    ) {}

    public function select(AuthorizeRequest $request): GatewayProvider
    {
        // Future Implementation:
        // 1. Check currency support (e.g., Stripe for USD, Adyen for EUR)
        // 2. Check card type (e.g., Amex to specific gateway)
        // 3. Check transaction amount (e.g., High value to specific gateway)
        // 4. Check tenant preferences via TenantContext

        // For now, return default if set
        if ($this->defaultProvider) {
            return $this->defaultProvider;
        }

        // If no default and no logic, we cannot select
        throw new GatewayException('No suitable gateway provider found for this request. Please specify a provider or configure a default.');
    }
}
