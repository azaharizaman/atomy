<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Services;

use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Contracts\GatewayRegistryInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Exceptions\GatewayNotFoundException;
use Nexus\PaymentGateway\Services\GatewayRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GatewayRegistry::class)]
final class GatewayRegistryTest extends TestCase
{
    private GatewayRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new GatewayRegistry();
    }

    #[Test]
    public function it_implements_registry_interface(): void
    {
        $this->assertInstanceOf(GatewayRegistryInterface::class, $this->registry);
    }

    #[Test]
    public function it_registers_gateway_class(): void
    {
        $gatewayClass = $this->createGatewayClass();

        $this->registry->register(GatewayProvider::STRIPE, $gatewayClass);

        $this->assertTrue($this->registry->has(GatewayProvider::STRIPE));
    }

    #[Test]
    public function it_throws_when_registering_non_gateway_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $this->registry->register(GatewayProvider::STRIPE, \stdClass::class);
    }

    #[Test]
    public function it_creates_gateway_instance(): void
    {
        $gatewayClass = $this->createGatewayClass();
        $this->registry->register(GatewayProvider::STRIPE, $gatewayClass);

        $gateway = $this->registry->create(GatewayProvider::STRIPE);

        $this->assertInstanceOf(GatewayInterface::class, $gateway);
    }

    #[Test]
    public function it_throws_when_creating_unregistered_gateway(): void
    {
        $this->expectException(GatewayNotFoundException::class);

        $this->registry->create(GatewayProvider::STRIPE);
    }

    #[Test]
    public function it_checks_if_provider_is_registered(): void
    {
        $this->assertFalse($this->registry->has(GatewayProvider::STRIPE));

        $gatewayClass = $this->createGatewayClass();
        $this->registry->register(GatewayProvider::STRIPE, $gatewayClass);

        $this->assertTrue($this->registry->has(GatewayProvider::STRIPE));
    }

    #[Test]
    public function it_returns_registered_providers(): void
    {
        $gatewayClass = $this->createGatewayClass();

        $this->registry->register(GatewayProvider::STRIPE, $gatewayClass);
        $this->registry->register(GatewayProvider::PAYPAL, $gatewayClass);

        $providers = $this->registry->getRegisteredProviders();

        $this->assertCount(2, $providers);
        $this->assertContainsEquals(GatewayProvider::STRIPE, $providers);
        $this->assertContainsEquals(GatewayProvider::PAYPAL, $providers);
    }

    #[Test]
    public function it_returns_empty_array_when_no_providers_registered(): void
    {
        $providers = $this->registry->getRegisteredProviders();

        $this->assertSame([], $providers);
    }

    #[Test]
    public function it_unregisters_provider(): void
    {
        $gatewayClass = $this->createGatewayClass();
        $this->registry->register(GatewayProvider::STRIPE, $gatewayClass);

        $this->assertTrue($this->registry->has(GatewayProvider::STRIPE));

        $this->registry->unregister(GatewayProvider::STRIPE);

        $this->assertFalse($this->registry->has(GatewayProvider::STRIPE));
    }

    #[Test]
    public function it_handles_unregistering_non_existent_provider(): void
    {
        // Should not throw
        $this->registry->unregister(GatewayProvider::STRIPE);

        $this->assertFalse($this->registry->has(GatewayProvider::STRIPE));
    }

    /**
     * Create an anonymous gateway class for testing.
     *
     * @return class-string<GatewayInterface>
     */
    private function createGatewayClass(): string
    {
        return get_class($this->createMock(GatewayInterface::class));
    }
}
