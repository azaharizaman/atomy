<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Contracts\GatewayManagerInterface;
use Nexus\PaymentGateway\Contracts\GatewayRegistryInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\RefundType;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\Exceptions\GatewayNotFoundException;
use Nexus\PaymentGateway\Services\GatewayManager;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(GatewayManager::class)]
final class GatewayManagerTest extends TestCase
{
    private GatewayRegistryInterface&MockObject $registry;
    private TenantContextInterface&MockObject $tenantContext;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private LoggerInterface&MockObject $logger;
    private GatewayManager $manager;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(GatewayRegistryInterface::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->tenantContext->method('getCurrentTenantId')->willReturn('tenant_123');

        $this->manager = new GatewayManager(
            registry: $this->registry,
            tenantContext: $this->tenantContext,
            eventDispatcher: $this->eventDispatcher,
            logger: $this->logger,
        );
    }

    #[Test]
    public function it_implements_manager_interface(): void
    {
        $this->assertInstanceOf(GatewayManagerInterface::class, $this->manager);
    }

    #[Test]
    public function it_registers_gateway(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');

        $this->registry->expects($this->once())
            ->method('create')
            ->with(GatewayProvider::STRIPE)
            ->willReturn($gateway);

        $gateway->expects($this->once())
            ->method('initialize')
            ->with($credentials);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Gateway registered', $this->anything());

        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $this->assertTrue($this->manager->hasGateway(GatewayProvider::STRIPE));
    }

    #[Test]
    public function it_returns_registered_gateway(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');

        $this->registry->method('create')->willReturn($gateway);

        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $this->assertSame($gateway, $this->manager->getGateway(GatewayProvider::STRIPE));
    }

    #[Test]
    public function it_throws_when_gateway_not_found(): void
    {
        $this->expectException(GatewayNotFoundException::class);

        $this->manager->getGateway(GatewayProvider::STRIPE);
    }

    #[Test]
    public function it_checks_if_gateway_exists(): void
    {
        $this->assertFalse($this->manager->hasGateway(GatewayProvider::STRIPE));

        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $this->assertTrue($this->manager->hasGateway(GatewayProvider::STRIPE));
    }

    #[Test]
    public function it_authorizes_payment(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, 'tok_visa');
        $expectedResult = AuthorizationResult::success(
            authorizationId: 'auth_123',
            amount: $amount,
            transactionId: 'txn_123',
        );

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $gateway->expects($this->once())
            ->method('authorize')
            ->with($request)
            ->willReturn($expectedResult);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $result = $this->manager->authorize(GatewayProvider::STRIPE, $request);

        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function it_captures_payment(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $amount = Money::of(10000, 'USD');
        $request = CaptureRequest::full('auth_123');
        $expectedResult = CaptureResult::success(
            captureId: 'cap_123',
            amount: $amount,
        );

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $gateway->expects($this->once())
            ->method('capture')
            ->with($request)
            ->willReturn($expectedResult);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $result = $this->manager->capture(GatewayProvider::STRIPE, $request);

        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function it_refunds_payment(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $amount = Money::of(10000, 'USD');
        $request = RefundRequest::full('txn_123');
        $expectedResult = RefundResult::success(
            refundId: 'ref_123',
            amount: $amount,
            type: RefundType::FULL,
        );

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $gateway->expects($this->once())
            ->method('refund')
            ->with($request)
            ->willReturn($expectedResult);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $result = $this->manager->refund(GatewayProvider::STRIPE, $request);

        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function it_voids_authorization(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $request = VoidRequest::create('auth_123');
        $expectedResult = VoidResult::success('void_123');

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $gateway->expects($this->once())
            ->method('void')
            ->with($request)
            ->willReturn($expectedResult);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $result = $this->manager->void(GatewayProvider::STRIPE, $request);

        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function it_returns_registered_providers(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $stripeCredentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $paypalCredentials = GatewayCredentials::forPayPal('client_123', 'secret_123');

        $this->registry->method('create')->willReturn($gateway);

        $this->manager->registerGateway(GatewayProvider::STRIPE, $stripeCredentials);
        $this->manager->registerGateway(GatewayProvider::PAYPAL, $paypalCredentials);

        $providers = $this->manager->getRegisteredProviders();

        $this->assertCount(2, $providers);
        $this->assertContainsEquals(GatewayProvider::STRIPE, $providers);
        $this->assertContainsEquals(GatewayProvider::PAYPAL, $providers);
    }

    #[Test]
    public function it_sets_and_gets_default_provider(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $this->assertNull($this->manager->getDefaultProvider());

        $this->manager->setDefaultProvider(GatewayProvider::STRIPE);

        $this->assertSame(GatewayProvider::STRIPE, $this->manager->getDefaultProvider());
    }

    #[Test]
    public function it_throws_when_setting_unregistered_default_provider(): void
    {
        $this->expectException(GatewayNotFoundException::class);

        $this->manager->setDefaultProvider(GatewayProvider::STRIPE);
    }

    #[Test]
    public function it_logs_and_dispatches_event_on_authorization_failure(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, 'tok_visa', 'order_123');

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $gateway->method('authorize')
            ->willThrowException(new \Nexus\PaymentGateway\Exceptions\AuthorizationFailedException(
                message: 'Card declined',
                gatewayErrorCode: 'CARD_DECLINED',
            ));

        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with('Gateway operation failed', $this->anything());

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->expectException(\Nexus\PaymentGateway\Exceptions\AuthorizationFailedException::class);

        $this->manager->authorize(GatewayProvider::STRIPE, $request);
    }

    #[Test]
    public function it_logs_and_dispatches_event_on_capture_failure(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $request = CaptureRequest::full('auth_123');

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $gateway->method('capture')
            ->willThrowException(new \Nexus\PaymentGateway\Exceptions\CaptureFailedException(
                message: 'Capture failed',
            ));

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->expectException(\Nexus\PaymentGateway\Exceptions\CaptureFailedException::class);

        $this->manager->capture(GatewayProvider::STRIPE, $request);
    }

    #[Test]
    public function it_logs_and_dispatches_event_on_refund_failure(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $request = RefundRequest::full('txn_123');

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $gateway->method('refund')
            ->willThrowException(new \Nexus\PaymentGateway\Exceptions\RefundFailedException(
                message: 'Refund failed',
            ));

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->expectException(\Nexus\PaymentGateway\Exceptions\RefundFailedException::class);

        $this->manager->refund(GatewayProvider::STRIPE, $request);
    }

    #[Test]
    public function it_logs_and_dispatches_event_on_void_failure(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $request = VoidRequest::create('auth_123');

        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $gateway->method('void')
            ->willThrowException(new \Nexus\PaymentGateway\Exceptions\VoidFailedException(
                message: 'Void failed',
            ));

        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->expectException(\Nexus\PaymentGateway\Exceptions\VoidFailedException::class);

        $this->manager->void(GatewayProvider::STRIPE, $request);
    }

    #[Test]
    public function it_dispatches_payment_failed_event_on_failed_authorization(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $credentials = GatewayCredentials::forStripe('sk_test_123', 'pk_test_123');
        $this->registry->method('create')->willReturn($gateway);
        $this->manager->registerGateway(GatewayProvider::STRIPE, $credentials);

        $request = new AuthorizeRequest(
            amount: Money::of(100, 'USD'),
            paymentMethodToken: 'tok_123',
            orderId: 'ord_123'
        );

        $result = new AuthorizationResult(
            success: false,
            authorizationId: null,
            transactionId: null,
            status: TransactionStatus::FAILED,
            error: \Nexus\PaymentGateway\ValueObjects\GatewayError::cardDeclined(message: 'Card declined')
        );

        $gateway->expects($this->once())
            ->method('authorize')
            ->with($request)
            ->willReturn($result);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(\Nexus\PaymentGateway\Events\PaymentFailedEvent::class));

        $this->manager->authorize(GatewayProvider::STRIPE, $request);
    }
}
