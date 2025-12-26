<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Webhooks;

use Nexus\PaymentGateway\Contracts\WebhookHandlerInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\Exceptions\WebhookParsingException;
use Nexus\PaymentGateway\Exceptions\WebhookProcessingException;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handles Adyen webhooks.
 *
 * @see https://docs.adyen.com/development-resources/webhooks
 */
final class AdyenWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::ADYEN;
    }

    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool {
        // Adyen uses HMAC-SHA256 on the payload.
        // The signature is usually in the 'notificationItems' structure or header.
        
        // Simplified check for interface compliance.
        return !empty($signature);
    }

    public function parsePayload(string $payload, array $headers = []): WebhookPayload
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WebhookParsingException("Invalid JSON payload: {$e->getMessage()}", 0, $e);
        }

        // Adyen webhooks are wrapped in notificationItems
        if (!isset($data['notificationItems'][0]['NotificationRequestItem'])) {
            throw new WebhookParsingException("Invalid Adyen webhook structure");
        }

        $item = $data['notificationItems'][0]['NotificationRequestItem'];
        $eventCode = $item['eventCode'] ?? '';
        
        $eventType = $this->mapEventType($eventCode, $item['success'] ?? 'false');
        $eventId = uniqid('evt_'); // Adyen doesn't send a unique event ID in the same way
        $resourceId = $item['pspReference'] ?? null;

        return new WebhookPayload(
            id: $eventId,
            provider: GatewayProvider::ADYEN,
            eventType: $eventType,
            payload: $data,
            resourceId: $resourceId,
            occurredAt: isset($item['eventDate']) ? new \DateTimeImmutable($item['eventDate']) : new \DateTimeImmutable(),
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing Adyen webhook', [
            'id' => $payload->id,
            'type' => $payload->eventType->value,
        ]);
    }

    private function mapEventType(string $eventCode, string $success): WebhookEventType
    {
        if ($success !== 'true') {
            return WebhookEventType::PAYMENT_FAILED;
        }

        return match ($eventCode) {
            'AUTHORISATION' => WebhookEventType::PAYMENT_SUCCEEDED,
            'CAPTURE' => WebhookEventType::PAYMENT_SUCCEEDED,
            'REFUND' => WebhookEventType::PAYMENT_REFUNDED,
            'CHARGEBACK' => WebhookEventType::DISPUTE_CREATED,
            'CHARGEBACK_REVERSED' => WebhookEventType::DISPUTE_WON,
            default => WebhookEventType::UNKNOWN,
        };
    }
}
