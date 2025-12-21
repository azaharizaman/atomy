<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebhookPayload::class)]
final class WebhookPayloadTest extends TestCase
{
    #[Test]
    public function it_creates_payload_with_all_parameters(): void
    {
        $receivedAt = new \DateTimeImmutable();

        $payload = new WebhookPayload(
            eventId: 'evt_123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'pi_456',
            resourceType: 'payment_intent',
            data: ['amount' => 10000],
            receivedAt: $receivedAt,
            signature: 'sig_abc',
            verified: true,
        );

        $this->assertSame('evt_123', $payload->eventId);
        $this->assertSame(WebhookEventType::PAYMENT_CAPTURED, $payload->eventType);
        $this->assertSame(GatewayProvider::STRIPE, $payload->provider);
        $this->assertSame('pi_456', $payload->resourceId);
        $this->assertSame('payment_intent', $payload->resourceType);
        $this->assertSame(['amount' => 10000], $payload->data);
        $this->assertSame($receivedAt, $payload->receivedAt);
        $this->assertSame('sig_abc', $payload->signature);
        $this->assertTrue($payload->verified);
    }

    #[Test]
    public function it_creates_from_stripe_webhook_event(): void
    {
        $event = [
            'id' => 'evt_stripe_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_stripe_456',
                    'object' => 'payment_intent',
                    'amount' => 10000,
                    'currency' => 'usd',
                ],
            ],
        ];

        $payload = WebhookPayload::fromStripe($event, 'sig_stripe', true);

        $this->assertSame('evt_stripe_123', $payload->eventId);
        $this->assertSame(WebhookEventType::PAYMENT_CAPTURED, $payload->eventType);
        $this->assertSame(GatewayProvider::STRIPE, $payload->provider);
        $this->assertSame('pi_stripe_456', $payload->resourceId);
        $this->assertSame('payment_intent', $payload->resourceType);
        $this->assertSame(10000, $payload->data['amount']);
        $this->assertSame('sig_stripe', $payload->signature);
        $this->assertTrue($payload->verified);
    }

    #[Test]
    #[DataProvider('stripeEventTypeMappingProvider')]
    public function it_maps_stripe_event_types(string $stripeType, WebhookEventType $expectedType): void
    {
        $event = [
            'id' => 'evt_test',
            'type' => $stripeType,
            'data' => ['object' => ['id' => 'obj_123']],
        ];

        $payload = WebhookPayload::fromStripe($event);

        $this->assertSame($expectedType, $payload->eventType);
    }

    public static function stripeEventTypeMappingProvider(): iterable
    {
        yield 'payment created' => ['payment_intent.created', WebhookEventType::PAYMENT_CREATED];
        yield 'payment succeeded' => ['payment_intent.succeeded', WebhookEventType::PAYMENT_CAPTURED];
        yield 'charge captured' => ['charge.captured', WebhookEventType::PAYMENT_CAPTURED];
        yield 'payment failed' => ['payment_intent.payment_failed', WebhookEventType::PAYMENT_FAILED];
        yield 'payment canceled' => ['payment_intent.canceled', WebhookEventType::PAYMENT_CANCELED];
        yield 'charge refunded' => ['charge.refunded', WebhookEventType::REFUND_COMPLETED];
        yield 'dispute created' => ['charge.dispute.created', WebhookEventType::DISPUTE_CREATED];
        yield 'dispute won' => ['charge.dispute.won', WebhookEventType::DISPUTE_WON];
        yield 'dispute lost' => ['charge.dispute.lost', WebhookEventType::DISPUTE_LOST];
        yield 'customer created' => ['customer.created', WebhookEventType::CUSTOMER_CREATED];
        yield 'payout created' => ['payout.created', WebhookEventType::PAYOUT_CREATED];
        yield 'payout paid' => ['payout.paid', WebhookEventType::PAYOUT_COMPLETED];
        yield 'payout failed' => ['payout.failed', WebhookEventType::PAYOUT_FAILED];
        yield 'unknown type defaults' => ['some.unknown.event', WebhookEventType::PAYMENT_CREATED];
    }

    #[Test]
    public function it_creates_from_paypal_webhook_event(): void
    {
        $event = [
            'id' => 'WH-paypal-123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource_type' => 'capture',
            'resource' => [
                'id' => 'CAP-paypal-456',
                'amount' => ['value' => '100.00', 'currency_code' => 'USD'],
            ],
        ];

        $payload = WebhookPayload::fromPayPal($event, 'sig_paypal', true);

        $this->assertSame('WH-paypal-123', $payload->eventId);
        $this->assertSame(WebhookEventType::PAYMENT_CAPTURED, $payload->eventType);
        $this->assertSame(GatewayProvider::PAYPAL, $payload->provider);
        $this->assertSame('CAP-paypal-456', $payload->resourceId);
        $this->assertSame('capture', $payload->resourceType);
        $this->assertSame('100.00', $payload->data['amount']['value']);
        $this->assertTrue($payload->verified);
    }

    #[Test]
    #[DataProvider('paypalEventTypeMappingProvider')]
    public function it_maps_paypal_event_types(string $paypalType, WebhookEventType $expectedType): void
    {
        $event = [
            'id' => 'WH-test',
            'event_type' => $paypalType,
            'resource' => ['id' => 'res_123'],
        ];

        $payload = WebhookPayload::fromPayPal($event);

        $this->assertSame($expectedType, $payload->eventType);
    }

    public static function paypalEventTypeMappingProvider(): iterable
    {
        yield 'order approved' => ['CHECKOUT.ORDER.APPROVED', WebhookEventType::PAYMENT_AUTHORIZED];
        yield 'capture completed' => ['PAYMENT.CAPTURE.COMPLETED', WebhookEventType::PAYMENT_CAPTURED];
        yield 'capture denied' => ['PAYMENT.CAPTURE.DENIED', WebhookEventType::PAYMENT_FAILED];
        yield 'capture refunded' => ['PAYMENT.CAPTURE.REFUNDED', WebhookEventType::REFUND_COMPLETED];
        yield 'dispute created' => ['CUSTOMER.DISPUTE.CREATED', WebhookEventType::DISPUTE_CREATED];
        yield 'dispute resolved' => ['CUSTOMER.DISPUTE.RESOLVED', WebhookEventType::DISPUTE_CLOSED];
        yield 'unknown type defaults' => ['SOME.UNKNOWN.EVENT', WebhookEventType::PAYMENT_CREATED];
    }

    #[Test]
    public function it_gets_data_values(): void
    {
        $payload = new WebhookPayload(
            eventId: 'evt_123',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'pi_456',
            data: [
                'amount' => 10000,
                'currency' => 'usd',
                'nested' => ['value' => 'test'],
            ],
        );

        $this->assertSame(10000, $payload->get('amount'));
        $this->assertSame('usd', $payload->get('currency'));
        $this->assertSame(['value' => 'test'], $payload->get('nested'));
        $this->assertNull($payload->get('nonexistent'));
        $this->assertSame('default', $payload->get('nonexistent', 'default'));
    }

    #[Test]
    public function it_identifies_payment_events(): void
    {
        $paymentEvent = new WebhookPayload(
            eventId: 'evt_1',
            eventType: WebhookEventType::PAYMENT_CAPTURED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'pi_123',
        );
        $this->assertTrue($paymentEvent->isPaymentEvent());
        $this->assertFalse($paymentEvent->isRefundEvent());
        $this->assertFalse($paymentEvent->isDisputeEvent());
    }

    #[Test]
    public function it_identifies_refund_events(): void
    {
        $refundEvent = new WebhookPayload(
            eventId: 'evt_2',
            eventType: WebhookEventType::REFUND_COMPLETED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'ref_123',
        );
        $this->assertFalse($refundEvent->isPaymentEvent());
        $this->assertTrue($refundEvent->isRefundEvent());
        $this->assertFalse($refundEvent->isDisputeEvent());
    }

    #[Test]
    public function it_identifies_dispute_events(): void
    {
        $disputeEvent = new WebhookPayload(
            eventId: 'evt_3',
            eventType: WebhookEventType::DISPUTE_CREATED,
            provider: GatewayProvider::STRIPE,
            resourceId: 'dp_123',
        );
        $this->assertFalse($disputeEvent->isPaymentEvent());
        $this->assertFalse($disputeEvent->isRefundEvent());
        $this->assertTrue($disputeEvent->isDisputeEvent());
    }

    #[Test]
    public function it_handles_empty_stripe_event(): void
    {
        $payload = WebhookPayload::fromStripe([]);

        $this->assertSame('', $payload->eventId);
        $this->assertSame('', $payload->resourceId);
        $this->assertNull($payload->resourceType);
    }

    #[Test]
    public function it_handles_empty_paypal_event(): void
    {
        $payload = WebhookPayload::fromPayPal([]);

        $this->assertSame('', $payload->eventId);
        $this->assertSame('', $payload->resourceId);
        $this->assertNull($payload->resourceType);
    }
}
