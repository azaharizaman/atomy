<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;

interface GatewayFactoryInterface
{
    /**
     * Create a gateway instance.
     *
     * @param GatewayProvider $provider Gateway provider enum
     * @param array<string, mixed> $config Gateway configuration
     * @return GatewayInterface
     */
    public function create(GatewayProvider $provider, array $config): GatewayInterface;

    /**
     * Check if gateway provider is supported.
     *
     * @param GatewayProvider $provider
     * @return bool
     */
    public function supports(GatewayProvider $provider): bool;

    /**
     * Get list of available gateway providers.
     *
     * @return array<GatewayProvider>
     */
    public function getAvailableGateways(): array;
}
