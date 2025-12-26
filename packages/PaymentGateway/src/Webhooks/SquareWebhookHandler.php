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
 * Handles Square webhooks.
 *
 * @see https://developer.squareup.com/docs/webhooks/overview
 */
final class SquareWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::SQUARE;
    }

    /**
     * Verify Square webhook signature.
     *
     * SECURITY: This is a simplified implementation that requires proper signature verification.
     * In production, this method MUST be replaced with full Square webhook verification:
     * 1. Extract the signature from x-square-signature header
     * 2. Concatenate the notification URL (from config) + payload body
     * 3. Compute HMAC-SHA256 using the webhook signature key
     * 4. Compare the computed signature with the received signature (constant-time comparison)
     * 5. Reject any webhooks that fail verification
     *
     * @see https://developer.squareup.com/docs/webhooks/step3validate
     *
     * WARNING: The current implementation accepts any non-empty signature and is NOT secure
     * for production use. Replace this with proper HMAC-SHA256 verification.
     */
    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool {
        // TODO: Implement full Square webhook signature verification
        // Square requires the notification URL to be included in the signed string,
        // which is not available in this interface. Consider updating the interface
        // or storing the URL in handler configuration.
        
        if (empty($signature) || empty($secret)) {
            return false;
        }
        
        // In production, implement proper HMAC-SHA256 verification:
        // $expectedSignature = base64_encode(hash_hmac('sha256', $notificationUrl . $payload, $secret, true));
        // return hash_equals($expectedSignature, $signature);
        
        return true;
    }

    public function parsePayload(string $payload, array $headers = []): WebhookPayload
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WebhookParsingException("Invalid JSON payload: {$e->getMessage()}", 0, $e);
        }

        if (!isset($data['type'])) {
            throw new WebhookParsingException("Missing 'type' in Square webhook payload");
        }

        $eventType = $this->mapEventType($data['type']);
        $eventId = $data['event_id'] ?? uniqid('evt_');
        $resourceId = $data['data']['object']['payment']['id'] ?? null;

        return new WebhookPayload(
            eventId: $eventId,
            eventType: $eventType,
            provider: GatewayProvider::SQUARE,
            resourceId: $resourceId,
            data: $data,
            receivedAt: isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : new \DateTimeImmutable(),
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing Square webhook', [
            'id' => $payload->eventId,
            'type' => $payload->eventType->value,
        ]);
    }

    private function mapEventType(string $squareEventType): WebhookEventType
    {
        return match ($squareEventType) {
            'payment.updated' => WebhookEventType::PAYMENT_SUCCEEDED, // Need to check status inside
            'refund.updated' => WebhookEventType::PAYMENT_REFUNDED,
            'dispute.created' => WebhookEventType::DISPUTE_CREATED,
            'dispute.state.updated' => WebhookEventType::DISPUTE_UPDATED,
            default => WebhookEventType::UNKNOWN,
        };
    }
}
