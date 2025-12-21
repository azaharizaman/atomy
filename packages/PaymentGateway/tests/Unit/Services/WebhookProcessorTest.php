<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Services;

use Nexus\PaymentGateway\Contracts\WebhookHandlerInterface;
use Nexus\PaymentGateway\Contracts\WebhookProcessorInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Nexus\PaymentGateway\Exceptions\WebhookVerificationFailedException;
use Nexus\PaymentGateway\Services\WebhookProcessor;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(WebhookProcessor::class)]
final class WebhookProcessorTest extends TestCase
{
    private TenantContextInterface&MockObject $tenantContext;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private LoggerInterface&MockObject $logger;
    private WebhookProcessor $processor;

    protected function setUp(): void
    {
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->tenantContext->method('getCurrentTenantId')->willReturn('tenant_123');

        $this->processor = new WebhookProcessor(
            tenantContext: $this->tenantContext,
            eventDispatcher: $this->eventDispatcher,
            logger: $this->logger,
        );
    }

    #[Test]
    public function it_implements_processor_interface(): void
    {
        $this->assertInstanceOf(WebhookProcessorInterface::class, $this->processor);
    }

    #[Test]
    public function it_sets_webhook_secret(): void
    {
        // Should not throw
        $this->processor->setSecret(GatewayProvider::STRIPE, 'whsec_test_secret');

        $this->assertTrue(true);
    }

    #[Test]
    public function it_registers_handler(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $this->processor->registerHandler($handler);

        $this->assertTrue($this->processor->hasHandler(GatewayProvider::STRIPE->value));
    }

    #[Test]
    public function it_checks_if_handler_exists(): void
    {
        $this->assertFalse($this->processor->hasHandler(GatewayProvider::STRIPE->value));

        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $this->processor->registerHandler($handler);

        $this->assertTrue($this->processor->hasHandler(GatewayProvider::STRIPE->value));
    }

    #[Test]
    public function it_processes_webhook_successfully(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $webhookPayload = new WebhookPayload(
            eventId: 'evt_123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'pi_123',
            resourceType: 'payment_intent',
            data: ['id' => 'pi_123', 'amount' => 1000],
            verified: true,
        );

        $handler->expects($this->once())
            ->method('verifySignature')
            ->with('{"id": "evt_123"}', 'test_signature', 'whsec_secret')
            ->willReturn(true);

        $handler->expects($this->once())
            ->method('parsePayload')
            ->willReturn($webhookPayload);

        $handler->expects($this->once())
            ->method('processWebhook')
            ->with($webhookPayload);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->processor->registerHandler($handler);
        $this->processor->setSecret(GatewayProvider::STRIPE, 'whsec_secret');

        $result = $this->processor->process(
            providerName: GatewayProvider::STRIPE->value,
            payload: '{"id": "evt_123"}',
            headers: ['stripe-signature' => 'test_signature'],
        );

        $this->assertSame($webhookPayload, $result);
        $this->assertTrue($result->verified);
    }

    #[Test]
    public function it_throws_when_no_handler_registered(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('No webhook handler registered');

        $this->processor->process(
            providerName: GatewayProvider::STRIPE->value,
            payload: '{}',
            headers: [],
        );
    }

    #[Test]
    public function it_throws_when_unknown_provider(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Unknown provider');

        $this->processor->process(
            providerName: 'unknown_gateway',
            payload: '{}',
            headers: [],
        );
    }

    #[Test]
    public function it_throws_when_verification_fails(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $handler->expects($this->once())
            ->method('verifySignature')
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Webhook signature verification failed', ['provider' => 'stripe']);

        $this->processor->registerHandler($handler);
        $this->processor->setSecret(GatewayProvider::STRIPE, 'whsec_secret');

        $this->expectException(WebhookVerificationFailedException::class);

        $this->processor->process(
            providerName: GatewayProvider::STRIPE->value,
            payload: '{}',
            headers: ['stripe-signature' => 'invalid'],
        );
    }

    #[Test]
    public function it_extracts_stripe_signature_from_headers(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $webhookPayload = new WebhookPayload(
            eventId: 'evt_123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'pi_123',
        );

        // Verify that the correct signature is extracted
        $handler->expects($this->once())
            ->method('verifySignature')
            ->with('{}', 'sig=test123', 'whsec_secret')
            ->willReturn(true);

        $handler->method('parsePayload')->willReturn($webhookPayload);
        $handler->method('processWebhook');

        $this->processor->registerHandler($handler);
        $this->processor->setSecret(GatewayProvider::STRIPE, 'whsec_secret');

        $result = $this->processor->process(
            providerName: GatewayProvider::STRIPE->value,
            payload: '{}',
            headers: ['stripe-signature' => 'sig=test123'],
        );

        $this->assertInstanceOf(WebhookPayload::class, $result);
    }

    #[Test]
    public function it_extracts_paypal_signature_from_headers(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::PAYPAL);

        $webhookPayload = new WebhookPayload(
            eventId: 'WH-123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::PAYPAL,
            resourceId: 'CAP-123',
        );

        // Verify that the PayPal signature header is extracted
        $handler->expects($this->once())
            ->method('verifySignature')
            ->with('{}', 'paypal_sig', 'paypal_secret')
            ->willReturn(true);

        $handler->method('parsePayload')->willReturn($webhookPayload);

        $this->processor->registerHandler($handler);
        $this->processor->setSecret(GatewayProvider::PAYPAL, 'paypal_secret');

        $result = $this->processor->process(
            providerName: GatewayProvider::PAYPAL->value,
            payload: '{}',
            headers: ['paypal-transmission-sig' => 'paypal_sig'],
        );

        $this->assertInstanceOf(WebhookPayload::class, $result);
    }

    #[Test]
    public function it_extracts_square_signature_from_headers(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::SQUARE);

        $webhookPayload = new WebhookPayload(
            eventId: 'sqe_123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::SQUARE,
            resourceId: 'pay_123',
        );

        // Verify that the Square signature header is extracted
        $handler->expects($this->once())
            ->method('verifySignature')
            ->with('{}', 'square_sig', 'square_secret')
            ->willReturn(true);

        $handler->method('parsePayload')->willReturn($webhookPayload);

        $this->processor->registerHandler($handler);
        $this->processor->setSecret(GatewayProvider::SQUARE, 'square_secret');

        $result = $this->processor->process(
            providerName: GatewayProvider::SQUARE->value,
            payload: '{}',
            headers: ['x-square-signature' => 'square_sig'],
        );

        $this->assertInstanceOf(WebhookPayload::class, $result);
    }

    #[Test]
    public function it_extracts_adyen_signature_from_headers(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::ADYEN);

        $webhookPayload = new WebhookPayload(
            eventId: 'adn_123',
            eventType: WebhookEventType::PAYMENT_AUTHORIZED,
            provider: GatewayProvider::ADYEN,
            resourceId: 'auth_123',
        );

        // Verify that the Adyen signature header is extracted
        $handler->expects($this->once())
            ->method('verifySignature')
            ->with('{}', 'adyen_sig', 'adyen_secret')
            ->willReturn(true);

        $handler->method('parsePayload')->willReturn($webhookPayload);

        $this->processor->registerHandler($handler);
        $this->processor->setSecret(GatewayProvider::ADYEN, 'adyen_secret');

        $result = $this->processor->process(
            providerName: GatewayProvider::ADYEN->value,
            payload: '{}',
            headers: ['x-adyen-hmac-signature' => 'adyen_sig'],
        );

        $this->assertInstanceOf(WebhookPayload::class, $result);
    }

    #[Test]
    public function it_handles_case_insensitive_headers(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $webhookPayload = new WebhookPayload(
            eventId: 'evt_123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'pi_123',
        );

        // Header with uppercase should still work
        $handler->expects($this->once())
            ->method('verifySignature')
            ->with('{}', 'test_sig', 'secret')
            ->willReturn(true);

        $handler->method('parsePayload')->willReturn($webhookPayload);

        $this->processor->registerHandler($handler);
        $this->processor->setSecret(GatewayProvider::STRIPE, 'secret');

        $result = $this->processor->process(
            providerName: GatewayProvider::STRIPE->value,
            payload: '{}',
            headers: ['Stripe-Signature' => 'test_sig'],
        );

        $this->assertInstanceOf(WebhookPayload::class, $result);
    }

    #[Test]
    public function it_logs_webhook_received(): void
    {
        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $webhookPayload = new WebhookPayload(
            eventId: 'evt_123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'pi_123',
        );

        $handler->method('verifySignature')->willReturn(true);
        $handler->method('parsePayload')->willReturn($webhookPayload);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Webhook received', [
                'provider' => 'stripe',
                'event_type' => WebhookEventType::PAYMENT_CAPTURED->value,
                'event_id' => 'evt_123',
            ]);

        $this->processor->registerHandler($handler);
        $this->processor->setSecret(GatewayProvider::STRIPE, 'secret');

        $this->processor->process(
            providerName: GatewayProvider::STRIPE->value,
            payload: '{}',
            headers: ['stripe-signature' => 'sig'],
        );
    }

    #[Test]
    public function it_works_without_event_dispatcher(): void
    {
        $processorWithoutDispatcher = new WebhookProcessor(
            tenantContext: $this->tenantContext,
            eventDispatcher: null,
            logger: $this->logger,
        );

        $handler = $this->createMock(WebhookHandlerInterface::class);
        $handler->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $webhookPayload = new WebhookPayload(
            eventId: 'evt_123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'pi_123',
        );

        $handler->method('verifySignature')->willReturn(true);
        $handler->method('parsePayload')->willReturn($webhookPayload);

        $processorWithoutDispatcher->registerHandler($handler);
        $processorWithoutDispatcher->setSecret(GatewayProvider::STRIPE, 'secret');

        // Should not throw
        $result = $processorWithoutDispatcher->process(
            providerName: GatewayProvider::STRIPE->value,
            payload: '{}',
            headers: ['stripe-signature' => 'sig'],
        );

        $this->assertInstanceOf(WebhookPayload::class, $result);
    }
}
