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
    /** @var array<string, class-string<GatewayInterface>> */
    private array $registrations = [];

    public function register(GatewayProvider $provider, string $gatewayClass): void
    {
        if (!is_a($gatewayClass, GatewayInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Gateway class %s must implement %s',
                $gatewayClass,
                GatewayInterface::class,
            ));
        }

        $this->registrations[$provider->value] = $gatewayClass;
    }

    public function create(GatewayProvider $provider): GatewayInterface
    {
        if (!$this->has($provider)) {
            throw new GatewayNotFoundException($provider);
        }

        $gatewayClass = $this->registrations[$provider->value];

        return new $gatewayClass();
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
