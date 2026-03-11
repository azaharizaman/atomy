<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Webhooks;

use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\Exceptions\WebhookParsingException;
use Nexus\PaymentGateway\Webhooks\StripeWebhookHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(StripeWebhookHandler::class)]
final class StripeWebhookHandlerTest extends TestCase
{
    private StripeWebhookHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new StripeWebhookHandler();
    }

    #[Test]
    public function it_verifies_valid_stripe_signature(): void
    {
        $payload = '{"id":"evt_123","type":"payment_intent.succeeded","created":1700000000}';
        $secret = 'whsec_test_secret';
        $timestamp = time();
        $header = $this->buildStripeSignatureHeader($payload, $secret, $timestamp);

        $result = $this->handler->verifySignature($payload, $header, $secret);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_rejects_stale_stripe_signature_timestamp(): void
    {
        $payload = '{"id":"evt_123","type":"payment_intent.succeeded","created":1700000000}';
        $secret = 'whsec_test_secret';
        $timestamp = time() - 600;
        $header = $this->buildStripeSignatureHeader($payload, $secret, $timestamp);

        $result = $this->handler->verifySignature($payload, $header, $secret);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_rejects_malformed_signature_header(): void
    {
        $payload = '{"id":"evt_123","type":"payment_intent.succeeded","created":1700000000}';
        $secret = 'whsec_test_secret';

        $result = $this->handler->verifySignature($payload, 'v1=missing_timestamp', $secret);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_throws_when_stripe_payload_has_no_event_id(): void
    {
        $this->expectException(WebhookParsingException::class);
        $this->expectExceptionMessage("Missing 'id' in Stripe webhook payload");

        $this->handler->parsePayload('{"type":"payment_intent.succeeded","created":1700000000}');
    }

    #[Test]
    public function it_parses_valid_stripe_payload(): void
    {
        $payload = '{"id":"evt_123","type":"payment_intent.succeeded","created":1700000000,"data":{"object":{"id":"pi_123"}}}';

        $parsed = $this->handler->parsePayload($payload);

        $this->assertSame('evt_123', $parsed->eventId);
        $this->assertSame('pi_123', $parsed->resourceId);
        $this->assertSame(WebhookEventType::PAYMENT_CAPTURED, $parsed->eventType);
    }

    private function buildStripeSignatureHeader(string $payload, string $secret, int $timestamp): string
    {
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return sprintf('t=%d,v1=%s', $timestamp, $signature);
    }
}
