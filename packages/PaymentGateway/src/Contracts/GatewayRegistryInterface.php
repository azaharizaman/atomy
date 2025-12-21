<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;

/**
 * Registry for gateway implementations.
 *
 * Responsible for creating and managing gateway instances.
 * Consumers can register custom gateway implementations.
 */
interface GatewayRegistryInterface
{
    /**
     * Register a gateway class for a provider.
     *
     * @param GatewayProvider $provider The gateway provider
     * @param class-string<GatewayInterface> $gatewayClass The gateway implementation class
     */
    public function register(GatewayProvider $provider, string $gatewayClass): void;

    /**
     * Create a gateway instance for a provider.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayNotFoundException
     */
    public function create(GatewayProvider $provider): GatewayInterface;

    /**
     * Check if a gateway is registered for a provider.
     */
    public function has(GatewayProvider $provider): bool;

    /**
     * Get all registered providers.
     *
     * @return array<GatewayProvider>
     */
    public function getRegisteredProviders(): array;

    /**
     * Remove a gateway registration.
     */
    public function unregister(GatewayProvider $provider): void;
}
