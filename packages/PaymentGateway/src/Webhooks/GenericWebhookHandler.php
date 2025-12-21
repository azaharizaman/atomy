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
 * Generic webhook handler for testing and simple integrations.
 *
 * Uses HMAC-SHA256 for signature verification.
 * Expects JSON payloads with standard structure:
 *
 * ```json
 * {
 *     "id": "evt_123",
 *     "type": "payment.succeeded",
 *     "created": 1234567890,
 *     "data": {
 *         "object": {
 *             "id": "ch_123",
 *             "amount": 1000,
 *             ...
 *         }
 *     }
 * }
 * ```
 */
final class GenericWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly GatewayProvider $provider = GatewayProvider::STRIPE,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return $this->provider;
    }

    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool {
        if (empty($secret)) {
            // Allow empty secret in test mode
            return true;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function parsePayload(string $payload, array $headers = []): WebhookPayload
    {
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WebhookParsingException(
                message: 'Failed to parse webhook JSON: ' . json_last_error_msg(),
                rawPayload: $payload,
            );
        }

        if (!is_array($data)) {
            throw new WebhookParsingException(
                message: 'Webhook payload must be a JSON object',
                rawPayload: $payload,
            );
        }

        // Extract event ID
        $eventId = $data['id'] ?? $data['event_id'] ?? null;
        if ($eventId === null) {
            throw new WebhookParsingException(
                message: 'Missing event ID in webhook payload',
                rawPayload: $payload,
            );
        }

        // Extract event type
        $eventTypeString = $data['type'] ?? $data['event_type'] ?? null;
        if ($eventTypeString === null) {
            throw new WebhookParsingException(
                message: 'Missing event type in webhook payload',
                rawPayload: $payload,
            );
        }

        $eventType = $this->mapEventType((string) $eventTypeString);

        // Extract timestamp
        $timestamp = null;
        if (isset($data['created'])) {
            $timestamp = new \DateTimeImmutable('@' . (int) $data['created']);
        } elseif (isset($data['timestamp'])) {
            $timestamp = new \DateTimeImmutable('@' . (int) $data['timestamp']);
        } else {
            $timestamp = new \DateTimeImmutable();
        }

        // Extract nested data
        $objectData = $data['data']['object'] ?? $data['data'] ?? $data;

        // Extract transaction reference
        $transactionId = $objectData['id'] ?? $objectData['transaction_id'] ?? null;

        return new WebhookPayload(
            eventId: (string) $eventId,
            eventType: $eventType,
            transactionId: $transactionId !== null ? (string) $transactionId : null,
            provider: $this->provider,
            receivedAt: new \DateTimeImmutable(),
            rawPayload: $data,
            data: $objectData,
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing webhook', [
            'provider' => $this->provider->value,
            'event_id' => $payload->eventId,
            'event_type' => $payload->eventType->value,
        ]);

        // Generic handler just logs - real handlers would dispatch events
        // or update internal state based on the webhook type
    }

    /**
     * Map webhook event type string to enum.
     */
    private function mapEventType(string $eventType): WebhookEventType
    {
        // Try exact match first
        $enumValue = WebhookEventType::tryFrom($eventType);
        if ($enumValue !== null) {
            return $enumValue;
        }

        // Map common gateway event types
        return match ($eventType) {
            // Stripe-style events
            'payment_intent.succeeded', 'charge.succeeded' => WebhookEventType::PAYMENT_SUCCEEDED,
            'payment_intent.payment_failed', 'charge.failed' => WebhookEventType::PAYMENT_FAILED,
            'charge.refunded' => WebhookEventType::REFUND_COMPLETED,
            'charge.refund.updated' => WebhookEventType::REFUND_FAILED,
            'charge.dispute.created' => WebhookEventType::DISPUTE_CREATED,
            'charge.dispute.closed' => WebhookEventType::DISPUTE_CLOSED,

            // PayPal-style events
            'PAYMENT.CAPTURE.COMPLETED' => WebhookEventType::PAYMENT_SUCCEEDED,
            'PAYMENT.CAPTURE.DENIED' => WebhookEventType::PAYMENT_FAILED,
            'PAYMENT.CAPTURE.REFUNDED' => WebhookEventType::REFUND_COMPLETED,

            // Square-style events
            'payment.completed' => WebhookEventType::PAYMENT_SUCCEEDED,
            'payment.failed' => WebhookEventType::PAYMENT_FAILED,
            'refund.created' => WebhookEventType::REFUND_COMPLETED,

            // Default to payment succeeded for unknown types
            default => WebhookEventType::PAYMENT_SUCCEEDED,
        };
    }
}
