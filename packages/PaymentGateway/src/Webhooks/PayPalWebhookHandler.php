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
 * Handles PayPal webhooks.
 *
 * @see https://developer.paypal.com/api/rest/webhooks/
 */
final class PayPalWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::PAYPAL;
    }

    /**
     * Verify PayPal webhook signature.
     *
     * SECURITY: This is a placeholder implementation that FAILS CLOSED for security.
     * In production, this method MUST be replaced with full PayPal webhook verification:
     * 1. Extract transmission headers (paypal-transmission-id, paypal-transmission-time, 
     *    paypal-transmission-sig, paypal-cert-url)
     * 2. Reconstruct the signed string according to PayPal's specification
     * 3. Fetch and validate the certificate from paypal-cert-url
     * 4. Verify the signature using the certificate and webhook secret
     * 5. Return true only if verification succeeds
     *
     * @see https://developer.paypal.com/api/rest/webhooks/#verify-webhook-signature
     * 
     * WARNING: This placeholder returns FALSE to prevent unauthorized webhook processing.
     * Implement proper verification before enabling PayPal webhooks in production.
     */
    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool {
        // TODO: Implement full PayPal webhook verification using their documented flow
        // Until implemented, fail closed to prevent webhook spoofing attacks
        
        // Reject if signature or secret is missing
        if (empty($signature) || empty($secret)) {
            return false;
        }
        
        // SECURITY: This placeholder fails closed (returns false) to prevent
        // unauthorized webhooks from being processed. In production, implement:
        // - Certificate chain validation
        // - HMAC-SHA256 signature verification
        // - Transmission timestamp validation to prevent replay attacks
        
        // Return false until proper verification is implemented
        return false;
    }

    public function parsePayload(string $payload, array $headers = []): WebhookPayload
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WebhookParsingException("Invalid JSON payload: {$e->getMessage()}", 0, $e);
        }

        if (!isset($data['event_type'])) {
            throw new WebhookParsingException("Missing 'event_type' in PayPal webhook payload");
        }

        $eventType = $this->mapEventType($data['event_type']);
        $eventId = $data['id'] ?? uniqid('evt_');
        $resourceId = $data['resource']['id'] ?? null;

        return new WebhookPayload(
            eventId: $eventId,
            eventType: $eventType,
            provider: GatewayProvider::PAYPAL,
            resourceId: $resourceId,
            resourceType: $data['resource_type'] ?? null,
            data: $data,
            receivedAt: isset($data['create_time']) ? new \DateTimeImmutable($data['create_time']) : new \DateTimeImmutable(),
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing PayPal webhook', [
            'id' => $payload->eventId,
            'type' => $payload->eventType->value,
        ]);

        // Logic to dispatch events or update local state would go here
        // Typically handled by the WebhookProcessor emitting events
    }

    private function mapEventType(string $paypalEventType): WebhookEventType
    {
        return match ($paypalEventType) {
            'PAYMENT.CAPTURE.COMPLETED' => WebhookEventType::PAYMENT_SUCCEEDED,
            'PAYMENT.CAPTURE.DENIED' => WebhookEventType::PAYMENT_FAILED,
            'PAYMENT.CAPTURE.REFUNDED' => WebhookEventType::PAYMENT_REFUNDED,
            'CUSTOMER.DISPUTE.CREATED' => WebhookEventType::DISPUTE_CREATED,
            'CUSTOMER.DISPUTE.RESOLVED' => WebhookEventType::DISPUTE_WON, // Or lost, depends on outcome
            default => WebhookEventType::UNKNOWN,
        };
    }
}
