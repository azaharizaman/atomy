<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Contracts\GatewayRegistryInterface;
use Nexus\PaymentGateway\Contracts\IdempotencyManagerInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\AuthorizationType;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Services\GatewayManager;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class GatewayManagerIdempotencyTest extends TestCase
{
    private GatewayManager $manager;
    private MockObject&GatewayRegistryInterface $registry;
    private MockObject&TenantContextInterface $tenantContext;
    private MockObject&IdempotencyManagerInterface $idempotencyManager;
    private MockObject&EventDispatcherInterface $eventDispatcher;
    private MockObject&LoggerInterface $logger;
    private MockObject&GatewayInterface $gateway;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(GatewayRegistryInterface::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
        $this->idempotencyManager = $this->createMock(IdempotencyManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->gateway = $this->createMock(GatewayInterface::class);

        $this->manager = new GatewayManager(
            $this->registry,
            $this->tenantContext,
            $this->idempotencyManager,
            $this->eventDispatcher,
            $this->logger
        );

        // Setup default gateway mock
        $this->registry->method('create')->willReturn($this->gateway);
        $this->manager->registerGateway(
            GatewayProvider::STRIPE,
            new GatewayCredentials(GatewayProvider::STRIPE, 'key', 'secret')
        );
    }

    public function test_authorize_uses_idempotency_manager(): void
    {
        $key = 'idempotency_key_' . uniqid();
        $request = new AuthorizeRequest(
            amount: Money::of(100, 'USD'),
            paymentMethodToken: 'tok_visa',
            authorizationType: AuthorizationType::AUTH_CAPTURE,
            idempotencyKey: $key
        );

        $expectedResult = new AuthorizationResult(
            success: true,
            authorizationId: 'auth_123',
            transactionId: 'txn_123',
            authorizedAmount: Money::of(100, 'USD'),
            rawResponse: []
        );

        $this->idempotencyManager->expects($this->once())
            ->method('execute')
            ->with(
                GatewayProvider::STRIPE,
                $this->equalTo($key),
                $this->isType('callable'),
                AuthorizationResult::class
            )
            ->willReturn($expectedResult);

        $result = $this->manager->authorize(GatewayProvider::STRIPE, $request);

        $this->assertSame($expectedResult, $result);
    }

    public function test_capture_uses_idempotency_manager(): void
    {
        $key = 'idempotency_key_' . uniqid();
        $request = new CaptureRequest(
            authorizationId: 'auth_123',
            amount: Money::of(100, 'USD'),
            idempotencyKey: $key
        );

        $expectedResult = new CaptureResult(
            success: true,
            captureId: 'cap_123',
            capturedAmount: Money::of(100, 'USD'),
            rawResponse: []
        );

        $this->idempotencyManager->expects($this->once())
            ->method('execute')
            ->with(
                GatewayProvider::STRIPE,
                $this->equalTo($key),
                $this->isType('callable'),
                CaptureResult::class
            )
            ->willReturn($expectedResult);

        $result = $this->manager->capture(GatewayProvider::STRIPE, $request);

        $this->assertSame($expectedResult, $result);
    }

    public function test_refund_uses_idempotency_manager(): void
    {
        $key = 'idempotency_key_' . uniqid();
        $request = new RefundRequest(
            transactionId: 'txn_123',
            amount: Money::of(50, 'USD'),
            idempotencyKey: $key
        );

        $expectedResult = new RefundResult(
            success: true,
            refundId: 're_123',
            refundedAmount: Money::of(50, 'USD'),
            rawResponse: []
        );

        $this->idempotencyManager->expects($this->once())
            ->method('execute')
            ->with(
                GatewayProvider::STRIPE,
                $this->equalTo($key),
                $this->isType('callable'),
                RefundResult::class
            )
            ->willReturn($expectedResult);

        $result = $this->manager->refund(GatewayProvider::STRIPE, $request);

        $this->assertSame($expectedResult, $result);
    }

    public function test_void_uses_idempotency_manager(): void
    {
        $key = 'idempotency_key_' . uniqid();
        $request = new VoidRequest(
            authorizationId: 'auth_123',
            idempotencyKey: $key
        );

        $expectedResult = new VoidResult(
            success: true,
            voidId: 'void_123',
            rawResponse: []
        );

        $this->idempotencyManager->expects($this->once())
            ->method('execute')
            ->with(
                GatewayProvider::STRIPE,
                $this->equalTo($key),
                $this->isType('callable'),
                VoidResult::class
            )
            ->willReturn($expectedResult);

        $result = $this->manager->void(GatewayProvider::STRIPE, $request);

        $this->assertSame($expectedResult, $result);
    }

    public function test_operations_without_idempotency_key_bypass_manager(): void
    {
        $request = new AuthorizeRequest(
            amount: Money::of(100, 'USD'),
            paymentMethodToken: 'tok_visa',
            authorizationType: AuthorizationType::AUTH_CAPTURE
        );

        $this->idempotencyManager->expects($this->never())
            ->method('execute');

        $this->gateway->expects($this->once())
            ->method('authorize')
            ->willReturn(new AuthorizationResult(
                success: true,
                authorizationId: 'auth_123',
                transactionId: 'txn_123',
                authorizedAmount: Money::of(100, 'USD'),
                rawResponse: []
            ));

        $this->manager->authorize(GatewayProvider::STRIPE, $request);
    }
}
