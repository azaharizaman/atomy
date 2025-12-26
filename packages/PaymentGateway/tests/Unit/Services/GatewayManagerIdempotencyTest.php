<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Contracts\GatewayRegistryInterface;
use Nexus\PaymentGateway\Contracts\IdempotencyManagerInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\Enums\AuthorizationType;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Services\GatewayManager;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class GatewayManagerIdempotencyTest extends TestCase
{
    private GatewayManager $manager;
    private GatewayRegistryInterface $registry;
    private TenantContextInterface $tenantContext;
    private IdempotencyManagerInterface $idempotencyManager;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(GatewayRegistryInterface::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
        $this->idempotencyManager = $this->createMock(IdempotencyManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = new GatewayManager(
            $this->registry,
            $this->tenantContext,
            $this->idempotencyManager,
            $this->eventDispatcher,
            $this->logger
        );
    }

    #[Test]
    public function it_uses_idempotency_manager_when_key_is_present(): void
    {
        $provider = GatewayProvider::STRIPE;
        $idempotencyKey = 'idem_123';
        $amount = Money::of(1000, 'USD');
        
        // Register gateway
        $gateway = $this->createMock(GatewayInterface::class);
        $this->registry->expects($this->once())
            ->method('create')
            ->with($provider)
            ->willReturn($gateway);
        
        $credentials = new GatewayCredentials($provider, 'test_key');
        $this->manager->registerGateway($provider, $credentials);
        
        $request = new AuthorizeRequest(
            amount: $amount,
            paymentMethodToken: 'tok_123',
            authorizationType: AuthorizationType::AUTH_CAPTURE,
            idempotencyKey: $idempotencyKey
        );

        $expectedResult = new AuthorizationResult(
            success: true,
            authorizationId: 'ref_123',
            transactionId: 'txn_123',
            authorizedAmount: $amount,
            rawResponse: []
        );

        $this->idempotencyManager->expects($this->once())
            ->method('execute')
            ->with(
                $provider,
                $idempotencyKey,
                $this->isType('callable'),
                AuthorizationResult::class
            )
            ->willReturn($expectedResult);

        $result = $this->manager->authorize($provider, $request);

        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function it_skips_idempotency_manager_when_key_is_missing(): void
    {
        $provider = GatewayProvider::STRIPE;
        $amount = Money::of(1000, 'USD');
        
        // Register gateway
        $gateway = $this->createMock(GatewayInterface::class);
        $this->registry->expects($this->once())
            ->method('create')
            ->with($provider)
            ->willReturn($gateway);
            
        $credentials = new GatewayCredentials($provider, 'test_key');
        $this->manager->registerGateway($provider, $credentials);
        
        $request = new AuthorizeRequest(
            amount: $amount,
            paymentMethodToken: 'tok_123',
            authorizationType: AuthorizationType::AUTH_CAPTURE,
            idempotencyKey: null
        );

        $expectedResult = new AuthorizationResult(
            success: true,
            authorizationId: 'ref_123',
            transactionId: 'txn_123',
            authorizedAmount: $amount,
            rawResponse: []
        );

        $gateway->expects($this->once())
            ->method('authorize')
            ->with($request)
            ->willReturn($expectedResult);

        $this->idempotencyManager->expects($this->never())
            ->method('execute');

        $result = $this->manager->authorize($provider, $request);

        $this->assertSame($expectedResult, $result);
    }
}
