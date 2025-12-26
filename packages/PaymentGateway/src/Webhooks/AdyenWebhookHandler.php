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

    /**
     * Verify Adyen webhook signature.
     *
     * SECURITY: This is a placeholder implementation that FAILS CLOSED for security.
     * In production, this method MUST be replaced with full Adyen HMAC verification:
     * 1. Extract the HMAC signature from x-adyen-hmac-signature header
     * 2. Construct the signing string from the notification payload according to Adyen's spec
     * 3. Compute HMAC-SHA256 using the HMAC key from credentials
     * 4. Base64 encode the result
     * 5. Compare with the received signature (constant-time comparison)
     * 6. Return true only if verification succeeds
     *
     * @see https://docs.adyen.com/development-resources/webhooks/verify-hmac-signatures
     *
     * WARNING: This placeholder returns FALSE to prevent unauthorized webhook processing.
     * Implement proper verification before enabling Adyen webhooks in production.
     */
    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool {
        // TODO: Implement full Adyen HMAC-SHA256 webhook verification
        // Until implemented, fail closed to prevent webhook spoofing attacks
        
        // Reject if signature or secret is missing
        if (empty($signature) || empty($secret)) {
            return false;
        }
        
        // SECURITY: This placeholder fails closed (returns false) to prevent
        // unauthorized webhooks from being processed. Adyen requires specific
        // payload fields to be concatenated in a specific order for signature verification.
        
        // In production, implement proper HMAC verification:
        // 1. Parse the notification items from the payload
        // 2. Extract and concatenate required fields in the correct order
        // 3. Compute HMAC-SHA256 and base64 encode
        // 4. Use hash_equals for constant-time comparison
        
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
            eventId: $eventId,
            eventType: $eventType,
            provider: GatewayProvider::ADYEN,
            resourceId: $resourceId,
            data: $data,
            receivedAt: isset($item['eventDate']) ? new \DateTimeImmutable($item['eventDate']) : new \DateTimeImmutable(),
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing Adyen webhook', [
            'id' => $payload->eventId,
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
