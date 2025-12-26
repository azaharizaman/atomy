<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Contracts\GatewayRegistryInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Exceptions\GatewayNotFoundException;

/**
 * Registry for gateway implementations.
 *
 * Stores gateway class mappings and creates instances on demand.
 */
final class GatewayRegistry implements GatewayRegistryInterface
{
    /** @var array<string, class-string<GatewayInterface>|callable(): GatewayInterface> */
    private array $registrations = [];

    public function register(GatewayProvider $provider, string|callable $gatewayClassOrFactory): void
    {
        if (is_string($gatewayClassOrFactory)) {
            if (!is_a($gatewayClassOrFactory, GatewayInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Gateway class %s must implement %s',
                    $gatewayClassOrFactory,
                    GatewayInterface::class,
                ));
            }
        }

        $this->registrations[$provider->value] = $gatewayClassOrFactory;
    }

    public function create(GatewayProvider $provider): GatewayInterface
    {
        if (!$this->has($provider)) {
            throw new GatewayNotFoundException($provider);
        }

        $entry = $this->registrations[$provider->value];

        if (is_callable($entry)) {
            return $entry();
        }

        return new $entry();
    }

    public function has(GatewayProvider $provider): bool
    {
        return isset($this->registrations[$provider->value]);
    }

    public function getRegisteredProviders(): array
    {
        return array_map(
            fn(string $key) => GatewayProvider::from($key),
            array_keys($this->registrations),
        );
    }

    public function unregister(GatewayProvider $provider): void
    {
        unset($this->registrations[$provider->value]);
    }
}
