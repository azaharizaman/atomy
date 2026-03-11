<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Webhooks;

use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\Exceptions\WebhookParsingException;
use Nexus\PaymentGateway\Webhooks\AdyenWebhookHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdyenWebhookHandler::class)]
final class AdyenWebhookHandlerTest extends TestCase
{
    private const HMAC_SECRET = 'MDEyMzQ1Njc4OUFCQ0RFRg==';

    private AdyenWebhookHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new AdyenWebhookHandler();
    }

    #[Test]
    public function it_verifies_adyen_signature_from_header(): void
    {
        $item = $this->buildNotificationItem();
        $payload = $this->buildPayload($item);
        $signature = $this->computeAdyenSignature($item, self::HMAC_SECRET);

        $result = $this->handler->verifySignature($payload, $signature, self::HMAC_SECRET);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_verifies_adyen_signature_from_payload_when_header_missing(): void
    {
        $item = $this->buildNotificationItem();
        $signature = $this->computeAdyenSignature($item, self::HMAC_SECRET);
        $item['additionalData']['hmacSignature'] = $signature;
        $payload = $this->buildPayload($item);

        $result = $this->handler->verifySignature($payload, '', self::HMAC_SECRET);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_rejects_adyen_signature_when_payload_fields_are_missing(): void
    {
        $item = $this->buildNotificationItem();
        unset($item['amount']);
        $payload = $this->buildPayload($item);

        $result = $this->handler->verifySignature($payload, 'invalid-signature', self::HMAC_SECRET);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_generates_deterministic_adyen_event_id(): void
    {
        $payload = $this->buildPayload($this->buildNotificationItem());

        $first = $this->handler->parsePayload($payload);
        $second = $this->handler->parsePayload($payload);

        $this->assertSame($first->eventId, $second->eventId);
        $this->assertStringStartsWith('ady_', $first->eventId);
        $this->assertSame(WebhookEventType::PAYMENT_AUTHORIZED, $first->eventType);
    }

    #[Test]
    public function it_throws_for_adyen_payload_without_psp_reference(): void
    {
        $item = $this->buildNotificationItem();
        unset($item['pspReference']);

        $this->expectException(WebhookParsingException::class);
        $this->expectExceptionMessage("Missing 'pspReference' in Adyen webhook payload");

        $this->handler->parsePayload($this->buildPayload($item));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNotificationItem(): array
    {
        return [
            'pspReference' => '9916178942012',
            'originalReference' => '',
            'merchantAccountCode' => 'TestMerchant',
            'merchantReference' => 'ORDER-123',
            'amount' => [
                'value' => 1999,
                'currency' => 'USD',
            ],
            'eventCode' => 'AUTHORISATION',
            'success' => 'true',
            'eventDate' => '2024-01-01T00:00:00+00:00',
            'additionalData' => [],
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildPayload(array $item): string
    {
        return json_encode(
            ['notificationItems' => [['NotificationRequestItem' => $item]]],
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    private function computeAdyenSignature(array $item, string $secret): string
    {
        $amount = $item['amount'];
        $parts = [
            (string) $item['pspReference'],
            (string) $item['originalReference'],
            (string) $item['merchantAccountCode'],
            (string) $item['merchantReference'],
            (string) $amount['value'],
            (string) $amount['currency'],
            (string) $item['eventCode'],
            (string) $item['success'],
        ];

        $escaped = array_map(
            static fn (string $part): string => str_replace(['\\', ':'], ['\\\\', '\\:'], $part),
            $parts
        );
        $signingData = implode(':', $escaped);
        $decodedSecret = base64_decode($secret, true);
        $hmacKey = $decodedSecret === false ? $secret : $decodedSecret;

        return base64_encode(hash_hmac('sha256', $signingData, $hmacKey, true));
    }
}
